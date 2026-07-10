<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use App\Models\Achat;
use App\Models\AchatPaiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\DebtRecoveryNotification;

class DetteController extends Controller
{
    public function index()
    {
        $boutique = Auth::user()->boutique;
        $boutiqueId = $boutique?->id;

        $dettes = Achat::with(['fournisseur', 'paiements', 'recharge', 'debitBoutique'])
            ->where('statut', 'dette')
            ->where(function ($query) use ($boutiqueId) {
                // Ma boutique voit : les dettes PARTAGÉES (null) + celles QUI LUI SONT ATTRIBUÉES
                $query->whereNull('debit_boutique_id')
                    ->orWhere('debit_boutique_id', $boutiqueId);
            })
            ->get()
            ->filter(function (Achat $achat) {
                return $achat->reste_a_payer > 0;
            });

        $montantTotalRestant = $dettes->sum(fn(Achat $achat) => $achat->reste_a_payer);

        return view('boutiquier.dettes.index', compact('boutique', 'dettes', 'montantTotalRestant'));
    }

    public function payer(Request $request, Achat $achat)
    {
        if ($achat->statut !== 'dette') {
            return back()->with('error', 'Cette dette est déjà réglée ou n’est pas éligible au recouvrement.');
        }

        $request->validate([
            'montant' => 'required|numeric|min:1',
        ]);

        $boutique = Auth::user()->boutique;
        if (! $boutique) {
            return back()->with('error', 'Boutique introuvable.');
        }

        // Une dette attribuée à une AUTRE boutique ne peut pas être réglée ici.
        if ($achat->debit_boutique_id !== null && (int) $achat->debit_boutique_id !== (int) $boutique->id) {
            return back()->with('error', 'Cette dette est attribuée à une autre boutique.');
        }

        $montant = round($request->input('montant'), 2);
        $reste = $achat->reste_a_payer;

        if ($montant > $reste) {
            return back()->with('error', 'Le montant saisi dépasse la dette restante.');
        }

        if ($montant > $boutique->solde) {
            return back()->with('error', 'Solde insuffisant pour cette opération.');
        }

        DB::transaction(function () use ($achat, $boutique, $montant) {
            AchatPaiement::create([
                'achat_id' => $achat->id,
                'boutique_id' => $boutique->id,
                'user_id' => Auth::id(),
                'montant' => $montant,
                'description' => 'Recouvrement dette fournisseur',
            ]);

            $boutique->decrement('solde', $montant);

            $achat->refresh();
            if ($achat->reste_a_payer <= 0) {
                $achat->update(['statut' => 'paye']);
            }
        });

        $this->notifyBoutiquierForDebtRecovery($boutique, $achat, $montant);

        return back()->with('success', 'Paiement enregistré. La dette a été partiellement recouvrée.');
    }

    protected function notifyBoutiquierForDebtRecovery($boutique, Achat $achat, float $montant)
    {
        $boutiqueUsers = \App\Models\User::where('boutique_id', $boutique->id)
            ->where('role', 'boutiquier')
            ->get();

        if ($boutiqueUsers->isEmpty()) {
            return;
        }

        Notification::send($boutiqueUsers, new DebtRecoveryNotification(
            'Paiement de dette enregistré',
            "Un paiement de " . number_format($montant, 0, ',', ' ') . " FCFA a été enregistré pour l'achat #{$achat->id}.",
            'Voir les dettes',
            route('boutiquier.dettes.index')
        ));
    }
}
