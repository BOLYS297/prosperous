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

                $stock = \App\Models\Stock::where('boutique_id', $boutiqueId)
                    ->where('produit_id', $produit->id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->quantite < $quantite) {
                    throw new \Exception('Stock insuffisant pour le produit ' . $produit->nom . '.');
                }

                $unitPrice = $produit->prix_vente;
                if ($isGrossiste) {
                    $prixGrossiste = \App\Models\PrixGrossiste::where('grossiste_id', $grossisteId)
                        ->where('produit_id', $produit->id)
                        ->first();

                    if (!$prixGrossiste) {
                        throw new \Exception('Aucun tarif grossiste défini pour le produit ' . $produit->nom . '.');
                    }

                    $unitPrice = $prixGrossiste->prix_vente;
                }

                $lineTotal = $unitPrice * $quantite;
                $total += $lineTotal;

                \App\Models\VenteLigne::create([
                    'vente_id' => $vente->id,
                    'produit_id' => $produit->id,
                    'quantite' => $quantite,
                    'prix_unitaire' => $unitPrice,
                    'est_grossiste' => $isGrossiste,
                ]);

                $stock->decrement('quantite', $quantite);
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

        $ventes = Vente::with(['lignes.produit', 'user'])
            ->where('boutique_id', $boutiqueId)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get();

        $totalJour = $ventes->sum('montant_total');

        return view('boutiquier.historique', compact('ventes', 'totalJour'));
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
            'produit_id' => 'required|exists:produits,id',
            'quantite' => 'required|integer|min:1',
            'is_grossiste' => 'nullable|boolean',
            'grossiste_id' => 'nullable|exists:grossistes,id',
        ]);

        $produit = \App\Models\Produit::findOrFail($request->produit_id);
        $isGrossiste = $request->boolean('is_grossiste');
        $grossisteId = $isGrossiste ? $request->grossiste_id : null;

        // Récupérer l'ancienne ligne de vente
        $venteLigne = $vente->lignes()->first();
        if (!$venteLigne) {
            return back()->with('error', 'Ligne de vente introuvable.');
        }

        // Vérifier le stock
        $oldQuantite = $venteLigne->quantite;
        $newQuantite = $request->quantite;
        $quantiteDiff = $newQuantite - $oldQuantite;

        $stock = \App\Models\Stock::where('boutique_id', $boutiqueId)
            ->where('produit_id', $request->produit_id)
            ->first();

        if (!$stock || $stock->quantite < $quantiteDiff) {
            return back()->with('error', 'Stock insuffisant pour cette modification.');
        }

        $unitPrice = $produit->prix_vente;

        if ($isGrossiste) {
            if (!$grossisteId) {
                return back()->with('error', 'Veuillez sélectionner un grossiste.');
            }

            $prixGrossiste = \App\Models\PrixGrossiste::where('grossiste_id', $grossisteId)
                ->where('produit_id', $produit->id)
                ->first();

            if (!$prixGrossiste) {
                return back()->with('error', 'Aucun tarif grossiste défini.');
            }

            $unitPrice = $prixGrossiste->prix_vente;
        }

        $total = $unitPrice * $newQuantite;

        DB::transaction(function () use ($vente, $venteLigne, $stock, $quantiteDiff, $total, $unitPrice, $newQuantite, $produit, $grossisteId, $isGrossiste, $boutiqueId) {
            // Mettre à jour la vente
            $vente->update([
                'montant_total' => $total,
                'grossiste_id' => $grossisteId,
            ]);

            // Mettre à jour la ligne
            $venteLigne->update([
                'produit_id' => $produit->id,
                'quantite' => $newQuantite,
                'prix_unitaire' => $unitPrice,
                'est_grossiste' => $isGrossiste,
            ]);

            // Ajuster le stock
            $stock->decrement('quantite', $quantiteDiff);

            // Mettre à jour le solde de la boutique
            $boutique = \App\Models\Boutique::find($boutiqueId);
            if ($boutique) {
                $ancienTotal = $vente->getOriginal('montant_total');
                $boutique->increment('solde', $total - $ancienTotal);
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
                $stock = \App\Models\Stock::where('boutique_id', $boutiqueId)
                    ->where('produit_id', $ligne->produit_id)
                    ->first();

                if ($stock) {
                    $stock->increment('quantite', $ligne->quantite);
                }
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
