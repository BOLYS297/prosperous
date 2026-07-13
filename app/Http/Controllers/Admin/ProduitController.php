<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grossiste;
use App\Models\PrixGrossiste;
use App\Models\Produit;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));
        $filter = $request->query('filter', '');

        // Closures de détection des champs manquants (sur les colonnes brutes).
        $sansPrixVente = fn ($w) => $w->whereNull('prix_vente')->orWhere('prix_vente', '<=', 0);
        $sansGrossiste = fn ($w) => $w->whereNull('prix_vente_grossiste')->orWhere('prix_vente_grossiste', '<=', 0);
        $sansReference = fn ($w) => $w->whereNull('reference')->orWhere('reference', '');
        $incomplet = function ($w) {
            $w->whereNull('prix_vente')->orWhere('prix_vente', '<=', 0)
                ->orWhereNull('prix_vente_grossiste')->orWhere('prix_vente_grossiste', '<=', 0)
                ->orWhereNull('reference')->orWhere('reference', '');
        };

        $produits = Produit::with(['stocks.boutique'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nom', 'like', "%{$q}%")
                        ->orWhere('reference', 'like', "%{$q}%")
                        ->orWhere('prix_achat', 'like', "%{$q}%")
                        ->orWhere('prix_vente', 'like', "%{$q}%");
                });
            })
            ->when($filter === 'sans_prix_vente', fn ($query) => $query->where($sansPrixVente))
            ->when($filter === 'sans_prix_grossiste', fn ($query) => $query->where($sansGrossiste))
            ->when($filter === 'sans_reference', fn ($query) => $query->where($sansReference))
            ->when($filter === 'incomplets', fn ($query) => $query->where($incomplet))
            ->orderBy('nom')
            ->get();

        // Compteurs globaux pour les pastilles de filtre.
        $counts = [
            'sans_prix_vente' => Produit::where($sansPrixVente)->count(),
            'sans_prix_grossiste' => Produit::where($sansGrossiste)->count(),
            'sans_reference' => Produit::where($sansReference)->count(),
            'incomplets' => Produit::where($incomplet)->count(),
        ];

        return view('admin.produits.index', compact('produits', 'q', 'filter', 'counts'));
    }

    public function create()
    {
        return view('admin.produits.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'prix_achat' => 'required|numeric|min:0',
            'prix_vente' => 'required|numeric|min:0',
            'prix_vente_grossiste' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->only(['nom', 'reference', 'prix_achat', 'prix_vente']);
        $data['prix_vente_grossiste'] = $request->filled('prix_vente_grossiste') ? $request->input('prix_vente_grossiste') : null;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('produits', 'public');
        }

        \App\Models\Produit::create($data);

        return redirect()->route('admin.produits.index')->with('success', 'Produit ajouté au catalogue avec succès.');
    }

    public function edit(\App\Models\Produit $produit)
    {
        return view('admin.produits.edit', compact('produit'));
    }

    public function update(Request $request, \App\Models\Produit $produit)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
            'prix_achat' => 'required|numeric|min:0',
            'prix_vente' => 'required|numeric|min:0',
            'prix_vente_grossiste' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->only(['nom', 'reference', 'prix_achat', 'prix_vente']);
        $data['prix_vente_grossiste'] = $request->filled('prix_vente_grossiste') ? $request->input('prix_vente_grossiste') : null;

        if ($request->hasFile('image')) {
            if ($produit->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($produit->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($produit->image);
            }
            $data['image'] = $request->file('image')->store('produits', 'public');
        }

        $produit->update($data);

        return redirect()->route('admin.produits.index')->with('success', 'Produit modifié avec succès.');
    }

    public function destroy(\App\Models\Produit $produit)
    {
        $produit->delete();
        return redirect()->route('admin.produits.index')->with('success', 'Produit supprimé du catalogue.');
    }

    public function stocks(\App\Models\Produit $produit)
    {
        $produit->load(['stocks.boutique']);

        // Séparer les stocks par type de boutique
        $stocks = $produit->stocks;
        $stocksBoutiques = $stocks->filter(function ($stock) {
            return $stock->boutique && $stock->boutique->type === 'boutique';
        })->sortBy('boutique.nom');

        $stocksMagasin = $stocks->filter(function ($stock) {
            return $stock->boutique && $stock->boutique->type === 'magasin';
        });

        $totalStock = $stocks->sum('quantite');

        return view('admin.produits.stocks', compact('produit', 'stocksBoutiques', 'stocksMagasin', 'totalStock'));
    }
}
