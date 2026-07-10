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
        ]);

        $user = Auth::user();
        $boutiqueId = $user->boutique_id;
        $isGrossiste = $request->boolean('is_grossiste');
        $grossisteId = $isGrossiste ? $request->grossiste_id : null;

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

        if ($isGrossiste && !$grossisteId) {
            return back()->with('error', 'Veuillez sélectionner un grossiste pour cette vente.');
        }

        DB::transaction(function () use ($lignesData, $user, $boutiqueId, $isGrossiste, $grossisteId, $request) {
            $total = 0;
            $vente = \App\Models\Vente::create([
                'boutique_id' => $boutiqueId,
                'user_id' => $user->id,
                'montant_total' => 0,
                'grossiste_id' => $grossisteId,
            ]);

            foreach ($lignesData as $ligneData) {
                $produit = \App\Models\Produit::findOrFail($ligneData['produit_id']);
                $quantite = intval($ligneData['quantite']);
                if ($quantite < 1) {
                    throw new \Exception('Quantité invalide pour le produit ' . $produit->nom);
                }

                // Override grossiste OPTIONNEL : un tarif spécifique défini pour CE grossiste
                // (table PrixGrossiste) écrase le prix grossiste du lot.
                $prixGrossisteOverride = null;
                if ($isGrossiste) {
                    $prixGrossisteOverride = \App\Models\PrixGrossiste::where('grossiste_id', $grossisteId)
                        ->where('produit_id', $produit->id)
                        ->first();
                }

                $consumedStocks = \App\Models\Stock::consumeForSale($boutiqueId, $produit->id, $quantite, $isGrossiste ? null : $produit->prix_vente);

                if ($isGrossiste && $prixGrossisteOverride) {
                    // Tarif grossiste spécifique : une seule ligne au prix du grossiste.
                    $unitPrice = (float) $prixGrossisteOverride->prix_vente;
                    $total += $unitPrice * $quantite;

                    \App\Models\VenteLigne::create([
                        'vente_id' => $vente->id,
                        'produit_id' => $produit->id,
                        'quantite' => $quantite,
                        'prix_unitaire' => $unitPrice,
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

                        \App\Models\VenteLigne::create([
                            'vente_id' => $vente->id,
                            'produit_id' => $produit->id,
                            'quantite' => $prodQty,
                            'prix_unitaire' => $avgUnitPrice,
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
        $produits = \App\Models\Produit::all();

        return view('boutiquier.ventes.edit', compact('vente', 'produits'));
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

        if ($isGrossiste && !$grossisteId) {
            return back()->with('error', 'Veuillez sélectionner un grossiste.');
        }

        $newLines = $request->input('lignes', []);

        $newLineMap = [];
        foreach ($newLines as $lineData) {
            $produit = \App\Models\Produit::find($lineData['produit_id']);
            if (!$produit) {
                return back()->with('error', 'Produit introuvable.');
            }

            // Override grossiste OPTIONNEL (tarif spécifique du grossiste) ; sinon prix du lot.
            $grossisteOverride = null;
            if ($isGrossiste) {
                $prixGrossiste = \App\Models\PrixGrossiste::where('grossiste_id', $grossisteId)
                    ->where('produit_id', $produit->id)
                    ->first();
                $grossisteOverride = $prixGrossiste ? (float) $prixGrossiste->prix_vente : null;
            }

            $newLineMap[] = [
                'produit_id' => $produit->id,
                'quantite' => (int) $lineData['quantite'],
                'grossiste_override' => $grossisteOverride,
                'fallback_price' => (float) $produit->prix_vente,
            ];
        }

        $oldTotal = $vente->montant_total;

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
