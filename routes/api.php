<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Produit;

Route::middleware('auth')->get('/produits/search', function (Request $request) {
    $query = $request->get('q', '');

    $produits = Produit::query()
        ->where(function ($q) use ($query) {
            $q->where('nom', 'like', "%{$query}%")
                ->orWhere('reference', 'like', "%{$query}%");
        })
        ->select('id', 'nom', 'reference')
        ->limit(20)
        ->get()
        ->map(function ($produit) {
            return [
                'id' => $produit->id,
                'label' => $produit->nom . ($produit->reference ? " ({$produit->reference})" : ''),
                'nom' => $produit->nom,
                'reference' => $produit->reference,
            ];
        });

    return response()->json($produits);
});
