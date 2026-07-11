<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\LogActivite;
use App\Models\User;
use App\Notifications\PendingActionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class BoutiqueController extends Controller
{
    public function index()
    {
        $boutiques = Boutique::orderBy('type')->orderBy('nom')->get();

        return view('admin.boutiques.index', compact('boutiques'));
    }

    /**
     * Renommer une boutique / changer son type (depuis le module Paramètres).
     */
    public function update(Request $request, Boutique $boutique)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:boutique,magasin',
        ]);

        $ancienNom = $boutique->nom;

        $boutique->update([
            'nom' => $validated['nom'],
            'type' => $validated['type'],
        ]);

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'admin.boutiques.update',
            'description' => "Boutique renommée : « {$ancienNom} » → « {$boutique->nom} » (type : {$boutique->type}).",
        ]);

        return back()->with('success', 'Point de vente « ' . $boutique->nom . ' » mis à jour.');
    }

    /**
     * Enregistrer un versement de cash (approvisionnement) : augmente le solde
     * de la boutique et notifie les boutiquiers concernés.
     */
    public function crediter(Request $request, Boutique $boutique)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
        ]);

        $montant = (float) $validated['montant'];
        $motif = trim($validated['motif'] ?? '') ?: 'Approvisionnement en cash';

        DB::transaction(function () use ($boutique, $montant, $motif) {
            $boutique->increment('solde', $montant);

            LogActivite::create([
                'user_id' => Auth::id(),
                'action' => 'admin.boutiques.crediter',
                'description' => "Crédit de " . money_format_app($montant) . " sur le solde de la boutique {$boutique->nom} ({$motif}).",
            ]);
        });

        $this->notifyBoutiquiers($boutique, $montant, $motif);

        return back()->with('success', 'Le solde de ' . $boutique->nom . ' a été crédité de ' . money_format_app($montant) . '.');
    }

    protected function notifyBoutiquiers(Boutique $boutique, float $montant, string $motif): void
    {
        $recipients = User::where('role', 'boutiquier')
            ->where('boutique_id', $boutique->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PendingActionNotification(
            'Solde crédité',
            'Votre solde a été crédité de ' . money_format_app($montant) . ' (' . $motif . ').',
            'Voir',
            route('boutiquier.dashboard'),
            [
                'type' => 'solde_credit',
                'montant' => $montant,
            ]
        ));
    }
}
