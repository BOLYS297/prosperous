<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HoraireConnexion;
use Illuminate\Http\Request;

class HoraireConnexionController extends Controller
{
    /**
     * Affiche le formulaire de gestion des tranches horaires pour tous les rôles
     */
    public function index()
    {
        $magasiniers = HoraireConnexion::forRole('magasinier')->get();
        $boutiquiers = HoraireConnexion::forRole('boutiquier')->get();

        return view('admin.horaires.index', compact('magasiniers', 'boutiquiers'));
    }

    /**
     * Enregistre une nouvelle tranche horaire
     */
    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required|in:magasinier,boutiquier',
            'jour_semaine' => 'required|integer|min:0|max:6',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
        ]);

        HoraireConnexion::create([
            'role' => $request->role,
            'jour_semaine' => $request->jour_semaine,
            'heure_debut' => $request->heure_debut . ':00',
            'heure_fin' => $request->heure_fin . ':00',
            'actif' => true,
        ]);

        return redirect()->route('admin.horaires.index')->with('success', 'Session principale ajoutée avec succès.');
    }

    /**
     * Supprime une tranche horaire
     */
    public function destroy(HoraireConnexion $horaireConnexion)
    {
        $horaireConnexion->delete();

        return redirect()->route('admin.horaires.index')->with('success', 'Session principale supprimée avec succès.');
    }

    /**
     * Active/désactive une tranche horaire
     */
    public function toggle(HoraireConnexion $horaireConnexion)
    {
        $horaireConnexion->update(['actif' => !$horaireConnexion->actif]);

        return redirect()->route('admin.horaires.index')->with('success', 'Tranche horaire mise à jour.');
    }
}
