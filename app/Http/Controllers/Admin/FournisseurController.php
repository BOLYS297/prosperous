<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index()
    {
        $fournisseurs = \App\Models\Fournisseur::all();
        return view('admin.fournisseurs.index', compact('fournisseurs'));
    }

    public function create()
    {
        return view('admin.fournisseurs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255'
        ]);

        \App\Models\Fournisseur::create($request->all());

        return redirect()->route('admin.fournisseurs.index')->with('success', 'Fournisseur ajouté avec succès.');
    }

    public function edit(\App\Models\Fournisseur $fournisseur)
    {
        return view('admin.fournisseurs.edit', compact('fournisseur'));
    }

    public function update(Request $request, \App\Models\Fournisseur $fournisseur)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255'
        ]);

        $fournisseur->update($request->all());

        return redirect()->route('admin.fournisseurs.index')->with('success', 'Fournisseur modifié avec succès.');
    }

    public function destroy(\App\Models\Fournisseur $fournisseur)
    {
        $fournisseur->delete();
        return redirect()->route('admin.fournisseurs.index')->with('success', 'Fournisseur supprimé.');
    }
}
