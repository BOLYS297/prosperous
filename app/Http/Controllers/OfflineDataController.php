<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfflineDataController extends Controller
{
    public function index(Request $request)
    {
        $produits = Produit::select(['id', 'nom', 'reference', 'prix_achat', 'prix_vente', 'image'])->get();

        $stocks = Stock::with(['produit:id,nom,reference,prix_achat,prix_vente,image'])->get()->map(function (Stock $stock) {
            return [
                'id' => $stock->id,
                'produit_id' => $stock->produit_id,
                'boutique_id' => $stock->boutique_id,
                'quantite' => $stock->quantite,
                'produit' => $stock->produit ? [
                    'id' => $stock->produit->id,
                    'nom' => $stock->produit->nom,
                    'reference' => $stock->produit->reference,
                    'prix_achat' => $stock->produit->prix_achat,
                    'prix_vente' => $stock->produit->prix_vente,
                    'image' => $stock->produit->image,
                ] : null,
            ];
        });

        return response()->json([
            'produits' => $produits,
            'stocks' => $stocks,
            'ventes' => $this->getVentesDuJour(),
        ]);
    }

    protected function getVentesDuJour()
    {
        $user = Auth::user();
        if (!$user) return [];
        $boutiqueId = $user->boutique_id;

        $ventes = \App\Models\Vente::with(['lignes.produit'])
            ->where('boutique_id', $boutiqueId)
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($vente) {
                return [
                    'id' => $vente->id,
                    'created_at' => $vente->created_at->toDateTimeString(),
                    'montant_total' => $vente->montant_total,
                    'lignes' => $vente->lignes->map(function ($ligne) {
                        return [
                            'produit_id' => $ligne->produit_id,
                            'quantite' => $ligne->quantite,
                            'prix_unitaire' => $ligne->prix_unitaire,
                            'produit' => $ligne->produit ? [
                                'id' => $ligne->produit->id,
                                'nom' => $ligne->produit->nom,
                            ] : null,
                        ];
                    })->toArray(),
                ];
            })->toArray();

        return $ventes;
    }
}
