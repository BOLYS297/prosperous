<?php

namespace App\Http\Controllers\Admin;

use App\Models\Grossiste;
use App\Models\Produit;
use App\Models\PrixGrossiste;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GrossisteController extends Controller
{
    public function index()
    {
        $grossistes = Grossiste::withCount('prixProduits')->orderBy('nom')->paginate(15);
        $totalProduits = Produit::count();

        return view('admin.grossistes.index', compact('grossistes', 'totalProduits'));
    }

    public function create()
    {
        return view('admin.grossistes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:grossistes,code',
            'contact' => 'nullable|string|max:255',
        ]);

        $grossiste = Grossiste::create($validated);

        // À la création, le grossiste hérite du prix grossiste par défaut de TOUS
        // les produits. L'admin pourra ensuite personnaliser certains tarifs.
        $created = $grossiste->syncDefaultPrices();

        return redirect()->route('admin.grossistes.index')
            ->with('success', "Grossiste créé. Tarifs par défaut appliqués à {$created} produit(s) — personnalisez-les via « Tarifs ».");
    }

    public function edit(Grossiste $grossiste)
    {
        return view('admin.grossistes.edit', compact('grossiste'));
    }

    public function update(Request $request, Grossiste $grossiste)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:grossistes,code,' . $grossiste->id,
            'contact' => 'nullable|string|max:255',
        ]);

        $grossiste->update($validated);

        return redirect()->route('admin.grossistes.index')
            ->with('success', 'Grossiste modifié avec succès.');
    }

    public function destroy(Grossiste $grossiste)
    {
        $grossiste->delete();
        return redirect()->route('admin.grossistes.index')
            ->with('success', 'Grossiste supprimé avec succès.');
    }

    public function pricing(Grossiste $grossiste)
    {
        $produits = Produit::orderBy('nom')->get();
        // Prix grossiste par défaut (lot ou prix client) pour pré-remplir.
        $defauts = Grossiste::defaultPriceMap();
        // Tarifs déjà personnalisés pour ce grossiste, indexés par produit.
        $existants = $grossiste->prixProduits()->get()->keyBy('produit_id');

        return view('admin.grossistes.pricing', compact('grossiste', 'produits', 'defauts', 'existants'));
    }

    public function updatePricing(Request $request, Grossiste $grossiste)
    {
        $request->validate([
            'prix.*.produit_id' => 'required|exists:produits,id',
            'prix.*.prix_achat' => 'nullable|numeric|min:0',
            'prix.*.prix_vente' => 'required|numeric|min:0',
        ]);

        foreach ($request->input('prix', []) as $prix) {
            PrixGrossiste::updateOrCreate(
                [
                    'grossiste_id' => $grossiste->id,
                    'produit_id' => $prix['produit_id'],
                ],
                [
                    'prix_achat' => $prix['prix_achat'] ?? 0,
                    'prix_vente' => $prix['prix_vente'],
                ]
            );
        }

        return redirect()->route('admin.grossistes.index')
            ->with('success', 'Tarifs du grossiste mis à jour avec succès.');
    }
}
