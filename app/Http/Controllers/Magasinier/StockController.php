<?php

namespace App\Http\Controllers\Magasinier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $boutiqueId = \Illuminate\Support\Facades\Auth::user()->boutique_id;
        $q = trim($request->query('q', ''));

        $produits = \App\Models\Produit::when($q, function ($query) use ($q) {
            $query->where('nom', 'like', "%{$q}%")
                ->orWhere('reference', 'like', "%{$q}%");
        })
            ->with(['stocks' => function ($query) use ($boutiqueId) {
                // Ordre FIFO (plus ancien lot d'abord) pour l'affichage des lots
                $query->where('boutique_id', $boutiqueId)
                    ->orderBy('created_at')
                    ->orderBy('id');
            }])
            ->orderBy('nom')
            ->get();

        return view('magasinier.stocks.index', compact('produits', 'q'));
    }
}
