<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VenteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'lignes' => 'required_without:produit_id|array|min:1',
            'lignes.*.produit_id' => 'required_with:lignes|exists:produits,id',
            'lignes.*.quantite' => 'required_with:lignes|integer|min:1',
            'produit_id' => 'required_without:lignes|exists:produits,id',
            'quantite' => 'required_without:lignes|integer|min:1',
            'is_grossiste' => 'nullable|boolean',
            'grossiste_id' => 'nullable|exists:grossistes,id',
            'mecanicien_id' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        $boutiqueId = $user->boutique_id;
        $isGrossiste = $request->boolean('is_grossiste');
        $grossisteId = $isGrossiste ? $request->grossiste_id : null;

        // Un mécanicien ne peut être crédité que sur une vente CLIENT, et
        // seulement s'il appartient à cette boutique.
        $mecanicien = null;
        if (! $isGrossiste && $request->filled('mecanicien_id')) {
            $mecanicien = \App\Models\User::where('id', $request->mecanicien_id)
                ->where('role', 'mecanicien')
                ->where('boutique_id', $boutiqueId)
                ->first();
        }

        $commissionPct = $mecanicien
            ? (float) ($mecanicien->commission_percent ?? param('mecanicien_commission_percent', 0))
            : 0.0;

        $lignesData = [];
        if ($request->filled('lignes')) {
            $lignesData = $request->input('lignes');
        } else {
            $lignesData = [
                [
                    'produit_id' => $request->produit_id,
                    'quantite' => $request->quantite,
                ],
            ];
        }

        // Le grossiste est OPTIONNEL : sans grossiste sélectionné, la vente utilise
        // le prix grossiste par défaut de chaque produit (lot, sinon prix client).

        try {
        DB::transaction(function () use ($lignesData, $user, $boutiqueId, $isGrossiste, $grossisteId, $request, $mecanicien, $commissionPct) {
            $total = 0;
            $vente = \App\Models\Vente::create([
                'boutique_id' => $boutiqueId,
                'user_id' => $user->id,
                'montant_total' => 0,
                'grossiste_id' => $grossisteId,
                'mecanicien_id' => $mecanicien?->id,
            ]);

            foreach ($lignesData as $ligneData) {
                $produit = \App\Models\Produit::findOrFail($ligneData['produit_id']);
                $quantite = intval($ligneData['quantite']);
                if ($quantite < 1) {
                    throw new \Exception('Quantité invalide pour le produit ' . $produit->nom);
                }

                // Prix grossiste FIXE : tarif spécifique de CE grossiste (override), sinon
                // prix grossiste PAR DÉFAUT du produit. Il s'applique à toute la ligne.
                $grossisteFixedPrice = null;
                if ($isGrossiste) {
                    $override = \App\Models\PrixGrossiste::where('grossiste_id', $grossisteId)
                        ->where('produit_id', $produit->id)
                        ->first();
                    if ($override && (float) $override->prix_vente > 0) {
                        $grossisteFixedPrice = (float) $override->prix_vente;
                    } else {
                        $base = $produit->getRawOriginal('prix_vente_grossiste');
                        if ($base !== null && (float) $base > 0) {
                            $grossisteFixedPrice = (float) $base;
                        }
                    }
                }

                $consumedStocks = \App\Models\Stock::consumeForSale($boutiqueId, $produit->id, $quantite, $isGrossiste ? null : $produit->prix_vente);

                // Coût FIGÉ à la vente (moyenne pondérée des lots consommés) :
                // indispensable pour calculer le bénéfice plus tard.
                $costTotal = 0;
                $costQty = 0;
                foreach ($consumedStocks as $cs) {
                    $costTotal += ((float) ($cs['stock']->prix_achat_unitaire ?? 0)) * (int) $cs['quantite'];
                    $costQty += (int) $cs['quantite'];
                }
                $avgCost = $costQty > 0 ? $costTotal / $costQty : 0.0;

                if ($isGrossiste && $grossisteFixedPrice !== null) {
                    // Vente grossiste : prix unique (tarif spécifique OU prix grossiste par défaut).
                    // Aucune commission mécanicien sur les ventes grossistes.
                    $unitPrice = $grossisteFixedPrice;
                    $total += $unitPrice * $quantite;

                    \App\Models\VenteLigne::create([
                        'vente_id' => $vente->id,
                        'produit_id' => $produit->id,
                        'quantite' => $quantite,
                        'prix_unitaire' => $unitPrice,
                        'prix_achat_unitaire' => $avgCost,
                        'commission_mecanicien' => null,
                        'est_grossiste' => true,
                    ]);
                } else {
                    // FIFO : chaque lot à SON prix.
                    //  - vente grossiste  -> prix grossiste du lot (repli : prix client du lot, puis prix produit)
                    //  - vente client     -> prix client du lot
                    $prodQty = 0;
                    $prodTotal = 0;
                    foreach ($consumedStocks as $consumedStock) {
                        if ($isGrossiste) {
                            $lotPrice = $consumedStock['prix_grossiste']
                                ?? $consumedStock['prix_unitaire']
                                ?? $produit->prix_vente;
                        } else {
                            $lotPrice = $consumedStock['prix_unitaire'] ?? $produit->prix_vente;
                        }
                        $lotQty = $consumedStock['quantite'];
                        $prodQty += $lotQty;
                        $prodTotal += $lotPrice * $lotQty;
                    }

                    if ($prodQty > 0) {
                        $avgUnitPrice = $prodTotal / $prodQty;
                        $total += $prodTotal;

                        // Commission mécanicien : pourcentage du BÉNÉFICE de l'article,
                        // uniquement sur une vente client attribuée à un mécanicien.
                        $commission = null;
                        if ($mecanicien && ! $isGrossiste && $commissionPct > 0) {
                            $benefice = ($avgUnitPrice - $avgCost) * $prodQty;
                            $commission = max(0, $benefice * $commissionPct / 100);
                        }

                        \App\Models\VenteLigne::create([
                            'vente_id' => $vente->id,
                            'produit_id' => $produit->id,
                            'quantite' => $prodQty,
                            'prix_unitaire' => $avgUnitPrice,
                            'prix_achat_unitaire' => $avgCost,
                            'commission_mecanicien' => $commission,
                            'est_grossiste' => $isGrossiste,
                        ]);
                    }
                }
            }

            $vente->update(['montant_total' => $total]);

            $boutique = \App\Models\Boutique::find($boutiqueId);
            if ($boutique) {
                $boutique->increment('solde', $total);
            }
        });
        } catch (\RuntimeException $e) {
            // Ex. "Stock insuffisant pour cette vente." -> message propre, pas de 500.
            return back()->with('error', $e->getMessage() ?: 'Stock insuffisant pour cette vente.')->withInput();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', "Une erreur est survenue lors de l'enregistrement du ticket.")->withInput();
        }

        $message = 'Ticket enregistré avec succès !';
        return back()->with('success', $message);
    }

    public function historique()
    {
        $boutiqueId = Auth::user()->boutique_id;

        // Historique HEBDOMADAIRE (lundi -> dimanche de la semaine en cours) :
        // les tickets restent téléchargeables toute la semaine.
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        $ventes = Vente::with(['lignes.produit', 'user'])
            ->where('boutique_id', $boutiqueId)
            ->whereBetween('created_at', [$debutSemaine, $finSemaine])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSemaine = $ventes->sum('montant_total');

        return view('boutiquier.historique', compact('ventes', 'totalSemaine', 'debutSemaine', 'finSemaine'));
    }

    public function show(Vente $vente)
    {
        $boutiqueId = Auth::user()->boutique_id;

        if ($vente->boutique_id !== $boutiqueId) {
            abort(403, 'Accès non autorisé.');
        }

        $vente->load(['lignes.produit', 'user']);

        return view('boutiquier.ventes.show', compact('vente'));
    }

    public function edit(Vente $vente)
    {
        $boutiqueId = Auth::user()->boutique_id;

        if ($vente->boutique_id !== $boutiqueId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que la vente peut être modifiée (max 24 heures après création)
        if ($vente->created_at->addHours(24)->isPast()) {
            return back()->with('error', 'Cette vente ne peut plus être modifiée (délai de 24h dépassé).');
        }

        $vente->load(['lignes.produit', 'user']);

        // Produits avec le stock de LA boutique (pour prix FIFO + dispo en direct)
        $produits = \App\Models\Produit::with(['stocks' => function ($query) use ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }])->orderBy('nom')->get();

        $grossistes = \App\Models\Grossiste::with('prixProduits')->get();

        return view('boutiquier.ventes.edit', compact('vente', 'produits', 'grossistes'));
    }

    public function update(Request $request, Vente $vente)
    {
        $boutiqueId = Auth::user()->boutique_id;

        if ($vente->boutique_id !== $boutiqueId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que la vente peut être modifiée
        if ($vente->created_at->addHours(24)->isPast()) {
            return back()->with('error', 'Cette vente ne peut plus être modifiée.');
        }

        $request->validate([
            'lignes' => 'required|array|min:1',
            'lignes.*.produit_id' => 'required|exists:produits,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'is_grossiste' => 'sometimes|in:0,1',
            'grossiste_id' => 'nullable|exists:grossistes,id',
        ]);

        $isGrossiste = $request->input('is_grossiste') === '1';
        $grossisteId = $isGrossiste ? $request->grossiste_id : null;

        // Grossiste optionnel (prix grossiste par défaut sinon).

        $newLines = $request->input('lignes', []);

        $newLineMap = [];
        foreach ($newLines as $lineData) {
            $produit = \App\Models\Produit::find($lineData['produit_id']);
            if (!$produit) {
                return back()->with('error', 'Produit introuvable.');
            }

            // Prix grossiste FIXE : tarif spécifique de CE grossiste (override), sinon
            // prix grossiste PAR DÉFAUT du produit.
            $grossisteOverride = null;
            if ($isGrossiste) {
                $prixGrossiste = \App\Models\PrixGrossiste::where('grossiste_id', $grossisteId)
                    ->where('produit_id', $produit->id)
                    ->first();
                if ($prixGrossiste && (float) $prixGrossiste->prix_vente > 0) {
                    $grossisteOverride = (float) $prixGrossiste->prix_vente;
                } else {
                    $base = $produit->getRawOriginal('prix_vente_grossiste');
                    if ($base !== null && (float) $base > 0) {
                        $grossisteOverride = (float) $base;
                    }
                }
            }

            $newLineMap[] = [
                'produit_id' => $produit->id,
                'quantite' => (int) $lineData['quantite'],
                'grossiste_override' => $grossisteOverride,
                'fallback_price' => (float) $produit->prix_vente,
            ];
        }

        $oldTotal = $vente->montant_total;

        try {
        DB::transaction(function () use ($vente, $boutiqueId, $newLineMap, $oldTotal, $grossisteId, $isGrossiste) {
            foreach ($vente->lignes as $existingLine) {
                \App\Models\Stock::restoreQuantity($boutiqueId, $existingLine->produit_id, $existingLine->quantite);
            }

            $vente->lignes()->delete();

            $newTotal = 0;
            foreach ($newLineMap as $lineData) {
                $consumedStocks = \App\Models\Stock::consumeForSale($boutiqueId, $lineData['produit_id'], $lineData['quantite'], $isGrossiste ? null : $lineData['fallback_price']);

                if ($isGrossiste && $lineData['grossiste_override'] !== null) {
                    // Tarif grossiste spécifique : une seule ligne.
                    $unitPrice = $lineData['grossiste_override'];
                    \App\Models\VenteLigne::create([
                        'vente_id' => $vente->id,
                        'produit_id' => $lineData['produit_id'],
                        'quantite' => $lineData['quantite'],
                        'prix_unitaire' => $unitPrice,
                        'est_grossiste' => true,
                    ]);

                    $newTotal += $unitPrice * $lineData['quantite'];
                } else {
                    // FIFO : chaque lot à son prix (grossiste par lot si vente grossiste).
                    foreach ($consumedStocks as $consumedStock) {
                        $lotQty = $consumedStock['quantite'];
                        if ($isGrossiste) {
                            $lotPrice = $consumedStock['prix_grossiste']
                                ?? $consumedStock['prix_unitaire']
                                ?? $lineData['fallback_price'];
                        } else {
                            $lotPrice = $consumedStock['prix_unitaire'] ?? $lineData['fallback_price'];
                        }

                        \App\Models\VenteLigne::create([
                            'vente_id' => $vente->id,
                            'produit_id' => $lineData['produit_id'],
                            'quantite' => $lotQty,
                            'prix_unitaire' => $lotPrice,
                            'est_grossiste' => $isGrossiste,
                        ]);

                        $newTotal += $lotPrice * $lotQty;
                    }
                }
            }

            $vente->update([
                'montant_total' => $newTotal,
                'grossiste_id' => $grossisteId,
            ]);

            $boutique = \App\Models\Boutique::find($boutiqueId);
            if ($boutique) {
                $boutique->increment('solde', $newTotal - $oldTotal);
            }
        });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage() ?: 'Stock insuffisant pour cette modification.')->withInput();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', "Une erreur est survenue lors de la modification de la vente.")->withInput();
        }

        return back()->with('success', 'Vente modifiée avec succès !');
    }

    public function destroy(Vente $vente)
    {
        $boutiqueId = Auth::user()->boutique_id;

        if ($vente->boutique_id !== $boutiqueId) {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier que la vente peut être supprimée (max 24 heures après création)
        if ($vente->created_at->addHours(24)->isPast()) {
            return back()->with('error', 'Cette vente ne peut plus être supprimée (délai de 24h dépassé).');
        }

        DB::transaction(function () use ($vente, $boutiqueId) {
            // Restaurer le stock
            foreach ($vente->lignes as $ligne) {
                \App\Models\Stock::restoreQuantity($boutiqueId, $ligne->produit_id, $ligne->quantite);
            }

            // Mettre à jour le solde de la boutique
            $boutique = \App\Models\Boutique::find($boutiqueId);
            if ($boutique) {
                $boutique->decrement('solde', $vente->montant_total);
            }

            // Soft delete la vente
            $vente->delete();
        });

        return redirect()->route('boutiquier.ventes.historique')->with('success', 'Vente supprimée avec succès !');
    }
}
