<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VenteController extends Controller
{
    /**
     * Prix d'achat PAR DÉFAUT d'un produit (colonne brute, hors accesseur).
     *
     * L'accesseur Produit::prix_achat renvoie le coût du lot actif quand il
     * existe ; ici on veut la valeur de référence saisie sur le produit
     * (inventaire initial), pour servir de repli quand un lot n'a pas de coût.
     * Null seulement si aucun prix d'achat n'est défini (cas défensif).
     */
    protected function prixAchatParDefaut(\App\Models\Produit $produit): ?float
    {
        $brut = $produit->getRawOriginal('prix_achat');

        return ($brut !== null && (float) $brut > 0) ? (float) $brut : null;
    }

    /**
     * Heure retenue pour la tarification hors heures.
     *
     * Par défaut l'heure du serveur. On n'accepte l'heure transmise par la caisse
     * QUE s'il s'agit d'un rejeu hors-ligne (en-tête X-Offline-Sync), et
     * uniquement si elle est plausible : ni dans le futur, ni vieille de plus de
     * 7 jours. Cela évite qu'une heure forgée déclenche une prime indue.
     */
    protected function momentVente(Request $request): \Illuminate\Support\Carbon
    {
        $maintenant = \Illuminate\Support\Carbon::now();

        if (! $request->hasHeader('X-Offline-Sync') || ! $request->filled('vendu_a')) {
            return $maintenant;
        }

        try {
            $venduA = \Illuminate\Support\Carbon::parse($request->input('vendu_a'));
        } catch (\Throwable $e) {
            return $maintenant;
        }

        if ($venduA->greaterThan($maintenant) || $venduA->lessThan($maintenant->copy()->subDays(7))) {
            return $maintenant;
        }

        return $venduA;
    }

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
            'vendu_a' => 'nullable|date',
        ]);

        $user = Auth::user();
        $boutiqueId = $user->boutique_id;
        $isGrossiste = $request->boolean('is_grossiste');
        $grossisteId = $isGrossiste ? $request->grossiste_id : null;

        // Moment de la vente : l'heure du SERVEUR fait foi. Exception : lors du
        // rejeu d'une vente enregistrée hors-ligne, on retient l'heure de la
        // caisse au moment de l'encaissement (sinon une vente de 18h50
        // synchronisée à 19h10 toucherait indûment la majoration hors heures).
        $venduA = $this->momentVente($request);

        // Tranche horaire marquée « majorée » par l'admin : prix majoré, la
        // différence revient au vendeur. Jamais sur une vente grossiste
        // (tarifs négociés).
        $horsHeures = ! $isGrossiste && \App\Support\TarifHoraire::estMajore($user, $venduA);

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
        DB::transaction(function () use ($lignesData, $user, $boutiqueId, $isGrossiste, $grossisteId, $request, $mecanicien, $commissionPct, $horsHeures) {
            $total = 0;
            $vente = \App\Models\Vente::create([
                'boutique_id' => $boutiqueId,
                'user_id' => $user->id,
                'montant_total' => 0,
                'grossiste_id' => $grossisteId,
                'mecanicien_id' => $mecanicien?->id,
                'hors_heures' => $horsHeures,
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
                // Chaque lot utilise SON prix d'achat ; s'il n'en a pas (stock
                // d'inventaire importé avant la mise en place du suivi des coûts),
                // on retombe sur le PRIX D'ACHAT PAR DÉFAUT du produit — que tous
                // les produits possèdent. Le coût ne reste inconnu que si même ce
                // repli est absent (produit sans prix d'achat, ce qui n'existe pas
                // ici mais reste géré défensivement).
                $defaultCost = $this->prixAchatParDefaut($produit);
                $costTotal = 0;
                $costQty = 0;
                $coutConnu = true;
                foreach ($consumedStocks as $cs) {
                    $lotCost = $cs['stock']->prix_achat_unitaire ?? $defaultCost;
                    if ($lotCost === null) {
                        $coutConnu = false;
                        break;
                    }
                    $costTotal += (float) $lotCost * (int) $cs['quantite'];
                    $costQty += (int) $cs['quantite'];
                }
                $avgCost = ($coutConnu && $costQty > 0) ? $costTotal / $costQty : null;

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
                        // Prix NORMAL de référence (heures d'ouverture).
                        $prixStandard = $prodTotal / $prodQty;
                        $avgUnitPrice = $prixStandard;
                        $prime = null;

                        // Hors heures : on facture le prix majoré et la différence
                        // revient à l'employé qui réalise la vente.
                        if ($horsHeures) {
                            $prixMajore = \App\Support\TarifHoraire::prixMajore($produit, $prixStandard);
                            if ($prixMajore > $prixStandard) {
                                $avgUnitPrice = $prixMajore;
                                $prime = ($prixMajore - $prixStandard) * $prodQty;
                            }
                        }

                        $total += $avgUnitPrice * $prodQty;

                        // Commission mécanicien : pourcentage du BÉNÉFICE de l'article,
                        // uniquement sur une vente client attribuée à un mécanicien.
                        // Elle se calcule sur le prix STANDARD : la majoration hors
                        // heures appartient au vendeur, pas au mécanicien.
                        // Sans coût connu, le bénéfice est incalculable : pas de commission
                        // (sinon elle porterait sur le chiffre d'affaires entier).
                        $commission = null;
                        if ($mecanicien && ! $isGrossiste && $commissionPct > 0 && $avgCost !== null) {
                            $benefice = ($prixStandard - $avgCost) * $prodQty;
                            $commission = max(0, $benefice * $commissionPct / 100);
                        }

                        \App\Models\VenteLigne::create([
                            'vente_id' => $vente->id,
                            'produit_id' => $produit->id,
                            'quantite' => $prodQty,
                            'prix_unitaire' => $avgUnitPrice,
                            'prix_unitaire_standard' => $prixStandard,
                            'prix_achat_unitaire' => $avgCost,
                            'commission_mecanicien' => $commission,
                            'prime_employe' => $prime,
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
                'default_cost' => $this->prixAchatParDefaut($produit),
            ];
        }

        // Le mécanicien reste porté par la vente (le formulaire d'édition ne le
        // renvoie pas) : on le recharge pour recréditer sa commission après
        // modification, sinon éditer un ticket effacerait sa marge.
        $mecanicien = $vente->mecanicien_id
            ? \App\Models\User::where('id', $vente->mecanicien_id)
                ->where('role', 'mecanicien')
                ->where('boutique_id', $boutiqueId)
                ->first()
            : null;
        $commissionPct = $mecanicien
            ? (float) ($mecanicien->commission_percent ?? param('mecanicien_commission_percent', 0))
            : 0.0;

        $oldTotal = $vente->montant_total;

        try {
        DB::transaction(function () use ($vente, $boutiqueId, $newLineMap, $oldTotal, $grossisteId, $isGrossiste, $mecanicien, $commissionPct) {
            foreach ($vente->lignes as $existingLine) {
                \App\Models\Stock::restoreQuantity($boutiqueId, $existingLine->produit_id, $existingLine->quantite);
            }

            $vente->lignes()->delete();

            $newTotal = 0;
            foreach ($newLineMap as $lineData) {
                $consumedStocks = \App\Models\Stock::consumeForSale($boutiqueId, $lineData['produit_id'], $lineData['quantite'], $isGrossiste ? null : $lineData['fallback_price']);

                // Coût FIGÉ, même règle que lors de la création : prix d'achat du
                // lot, sinon prix d'achat par défaut du produit. Sans ce gel,
                // modifier un ticket effaçait le coût (ligne « sans coût »).
                $costTotal = 0;
                $costQty = 0;
                $coutConnu = true;
                foreach ($consumedStocks as $cs) {
                    $lotCost = $cs['stock']->prix_achat_unitaire ?? $lineData['default_cost'];
                    if ($lotCost === null) {
                        $coutConnu = false;
                        break;
                    }
                    $costTotal += (float) $lotCost * (int) $cs['quantite'];
                    $costQty += (int) $cs['quantite'];
                }
                $avgCost = ($coutConnu && $costQty > 0) ? $costTotal / $costQty : null;

                if ($isGrossiste && $lineData['grossiste_override'] !== null) {
                    // Tarif grossiste spécifique : une seule ligne. Pas de commission.
                    $unitPrice = $lineData['grossiste_override'];
                    \App\Models\VenteLigne::create([
                        'vente_id' => $vente->id,
                        'produit_id' => $lineData['produit_id'],
                        'quantite' => $lineData['quantite'],
                        'prix_unitaire' => $unitPrice,
                        'prix_achat_unitaire' => $avgCost,
                        'commission_mecanicien' => null,
                        'est_grossiste' => true,
                    ]);

                    $newTotal += $unitPrice * $lineData['quantite'];
                } else {
                    // FIFO agrégé par produit (comme à la création) : une ligne
                    // porteuse du coût figé et de la commission éventuelle.
                    $prodQty = 0;
                    $prodTotal = 0;
                    foreach ($consumedStocks as $consumedStock) {
                        if ($isGrossiste) {
                            $lotPrice = $consumedStock['prix_grossiste']
                                ?? $consumedStock['prix_unitaire']
                                ?? $lineData['fallback_price'];
                        } else {
                            $lotPrice = $consumedStock['prix_unitaire'] ?? $lineData['fallback_price'];
                        }
                        $lotQty = $consumedStock['quantite'];
                        $prodQty += $lotQty;
                        $prodTotal += $lotPrice * $lotQty;
                    }

                    if ($prodQty > 0) {
                        // Une modification est une correction : pas de majoration
                        // hors heures rétroactive. Le prix standard = prix facturé.
                        $prixStandard = $prodTotal / $prodQty;

                        // Commission mécanicien recalculée sur le bénéfice réel,
                        // uniquement en vente client avec coût connu.
                        $commission = null;
                        if ($mecanicien && ! $isGrossiste && $commissionPct > 0 && $avgCost !== null) {
                            $benefice = ($prixStandard - $avgCost) * $prodQty;
                            $commission = max(0, $benefice * $commissionPct / 100);
                        }

                        \App\Models\VenteLigne::create([
                            'vente_id' => $vente->id,
                            'produit_id' => $lineData['produit_id'],
                            'quantite' => $prodQty,
                            'prix_unitaire' => $prixStandard,
                            'prix_unitaire_standard' => $prixStandard,
                            'prix_achat_unitaire' => $avgCost,
                            'commission_mecanicien' => $commission,
                            'est_grossiste' => $isGrossiste,
                        ]);

                        $newTotal += $prixStandard * $prodQty;
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
