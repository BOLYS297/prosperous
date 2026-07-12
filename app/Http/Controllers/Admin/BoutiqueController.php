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

        // Encours d'avances de caisse (non remboursées) par boutique.
        $avancesEnCours = \App\Models\AvanceCaisse::where('statut', 'en_cours')
            ->selectRaw('boutique_id, SUM(montant - montant_rembourse) as total')
            ->groupBy('boutique_id')
            ->pluck('total', 'boutique_id');

        return view('admin.boutiques.index', compact('boutiques', 'avancesEnCours'));
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
     * Enregistrer un versement de cash sur le solde d'une boutique, en deux modes :
     *  - simple : approvisionnement définitif (aucun remboursement) ;
     *  - dette  : avance de caisse à rembourser progressivement par la boutique.
     * Dans les deux cas le solde augmente ; en mode dette, une AvanceCaisse est créée.
     */
    public function crediter(Request $request, Boutique $boutique)
    {
        $validated = $request->validate([
            'montant' => 'required|numeric|min:1',
            'motif' => 'nullable|string|max:255',
            'mode' => 'required|in:simple,dette',
        ]);

        $montant = (float) $validated['montant'];
        $isDette = $validated['mode'] === 'dette';
        $motif = trim($validated['motif'] ?? '') ?: ($isDette ? 'Avance de caisse à rembourser' : 'Approvisionnement en cash');

        DB::transaction(function () use ($boutique, $montant, $motif, $isDette) {
            $boutique->increment('solde', $montant);

            if ($isDette) {
                \App\Models\AvanceCaisse::create([
                    'boutique_id' => $boutique->id,
                    'admin_id' => Auth::id(),
                    'montant' => $montant,
                    'montant_rembourse' => 0,
                    'motif' => $motif,
                    'statut' => 'en_cours',
                ]);
            }

            LogActivite::create([
                'user_id' => Auth::id(),
                'action' => $isDette ? 'admin.boutiques.avance' : 'admin.boutiques.crediter',
                'description' => ($isDette ? 'Avance de caisse de ' : 'Crédit de ') . money_format_app($montant) . " sur le solde de la boutique {$boutique->nom} ({$motif}).",
            ]);
        });

        $this->notifyBoutiquiers($boutique, $montant, $motif, $isDette);

        $message = $isDette
            ? 'Avance de ' . money_format_app($montant) . ' créditée à ' . $boutique->nom . ' (à rembourser par la boutique).'
            : 'Le solde de ' . $boutique->nom . ' a été crédité de ' . money_format_app($montant) . '.';

        return back()->with('success', $message);
    }

    protected function notifyBoutiquiers(Boutique $boutique, float $montant, string $motif, bool $isDette = false): void
    {
        $recipients = User::where('role', 'boutiquier')
            ->where('boutique_id', $boutique->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $message = $isDette
            ? 'Une avance de ' . money_format_app($montant) . ' a été créditée sur votre solde, à rembourser progressivement (' . $motif . ').'
            : 'Votre solde a été crédité de ' . money_format_app($montant) . ' (' . $motif . ').';

        Notification::send($recipients, new PendingActionNotification(
            $isDette ? 'Avance de caisse à rembourser' : 'Solde crédité',
            $message,
            $isDette ? 'Voir les dettes' : 'Voir',
            $isDette ? route('boutiquier.dettes.index') : route('boutiquier.dashboard'),
            [
                'type' => $isDette ? 'avance_caisse' : 'solde_credit',
                'montant' => $montant,
            ]
        ));
    }
}
