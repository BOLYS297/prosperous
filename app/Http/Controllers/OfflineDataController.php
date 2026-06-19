<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Stock;
use Illuminate\Http\Request;

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
        ]);
    }
}
