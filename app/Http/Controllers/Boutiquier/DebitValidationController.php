<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use App\Models\AchatPaiement;
use App\Models\Achat;
use App\Models\DebitValidation;
use App\Models\Depense;
use App\Models\LogActivite;
use App\Notifications\AdminValidationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebitValidationController extends Controller
{
    /**
     * Le boutiquier valide le débit : son solde est débité et l'achat/dépense
     * est finalisé. L'admin initiateur est notifié.
     */
    public function confirmer(Request $request, DebitValidation $validation)
    {
        // Contrôle d'accès (pas une course) : le débit doit concerner ma boutique.
        if ($validation->boutique_id !== Auth::user()->boutique_id) {
            abort(403, 'Ce débit ne concerne pas votre boutique.');
        }

        $alreadyProcessed = false;

        DB::transaction(function () use ($validation, &$alreadyProcessed) {
            // Verrou + re-vérification atomique du statut : sans cela, un
            // double-clic (ou un rejeu) débitait le solde et enregistrait le
            // paiement DEUX fois — le contrôle isPending() se faisait hors
            // transaction, donc deux requêtes concurrentes le passaient toutes
            // les deux avant que l'une ne bascule le statut.
            $fresh = DebitValidation::where('id', $validation->id)->lockForUpdate()->first();
            if (! $fresh || ! $fresh->isPending()) {
                $alreadyProcessed = true;
                return;
            }

            $boutique = $fresh->boutique()->lockForUpdate()->first();
            $boutique->decrement('solde', $fresh->amount);

            if ($fresh->source_type === 'achat') {
                // Enregistrer le paiement (l'achat devient réellement payé).
                AchatPaiement::create([
                    'achat_id' => $fresh->source_id,
                    'boutique_id' => $fresh->boutique_id,
                    'user_id' => Auth::id(),
                    'montant' => $fresh->amount,
                    'description' => 'Paiement comptant validé pour l\'achat #' . $fresh->source_id,
                ]);
            } elseif ($fresh->source_type === 'depense') {
                $depense = Depense::find($fresh->source_id);
                if ($depense) {
                    $depense->update([
                        'statut' => 'approved',
                        'validated_at' => now(),
                    ]);
                }
            } elseif ($fresh->source_type === 'recette') {
                // La recette quitte la caisse de la boutique et entre dans le
                // solde personnel de l'administrateur qui l'a demandée.
                \App\Models\AdminSoldeMouvement::enregistrer(
                    $fresh->initiator_id,
                    'recette',
                    (float) $fresh->amount,
                    $fresh->motif ?: 'Récupération des recettes',
                    $fresh->boutique_id,
                    'recette',
                    $fresh->id
                );
            }

            $fresh->update([
                'status' => 'confirmed',
                'responder_id' => Auth::id(),
                'responded_at' => now(),
            ]);
        });

        if ($alreadyProcessed) {
            return back()->with('error', 'Ce débit a déjà été traité.');
        }

        $validation->refresh();
        $this->notifyAdmin($validation, true);

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'boutiquier.debit.confirm',
            'description' => 'Débit validé (' . $validation->source_label . ') : ' . money_format_app($validation->amount) . '.',
        ]);

        return back()->with('success', 'Débit validé. Votre solde a été mis à jour.');
    }

    /**
     * Le boutiquier conteste le débit : aucun débit n'est appliqué, l'admin est
     * notifié du litige.
     */
    public function contester(Request $request, DebitValidation $validation)
    {
        if ($validation->boutique_id !== Auth::user()->boutique_id) {
            abort(403, 'Ce débit ne concerne pas votre boutique.');
        }

        $alreadyProcessed = false;

        DB::transaction(function () use ($validation, &$alreadyProcessed) {
            // Verrou + re-vérification atomique : une contestation ne rejette la
            // dépense et ne notifie l'admin qu'une seule fois.
            $fresh = DebitValidation::where('id', $validation->id)->lockForUpdate()->first();
            if (! $fresh || ! $fresh->isPending()) {
                $alreadyProcessed = true;
                return;
            }

            if ($fresh->source_type === 'depense') {
                $depense = Depense::find($fresh->source_id);
                if ($depense) {
                    $depense->update([
                        'statut' => 'rejected',
                        'rejet_reason' => 'Débit contesté par la boutique.',
                    ]);
                }
            }

            $fresh->update([
                'status' => 'rejected',
                'responder_id' => Auth::id(),
                'responded_at' => now(),
            ]);
        });

        if ($alreadyProcessed) {
            return back()->with('error', 'Ce débit a déjà été traité.');
        }

        $validation->refresh();
        $this->notifyAdmin($validation, false);

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'boutiquier.debit.dispute',
            'description' => 'Débit contesté (' . $validation->source_label . ') : ' . money_format_app($validation->amount) . '.',
        ]);

        return back()->with('success', 'Débit contesté. L\'administrateur a été notifié ; votre solde n\'a pas été débité.');
    }

    protected function notifyAdmin(DebitValidation $validation, bool $confirmed): void
    {
        $admin = $validation->initiator;
        if (! $admin) {
            return;
        }

        $boutiqueNom = $validation->boutique->nom ?? 'une boutique';
        $montant = money_format_app($validation->amount);

        if ($confirmed) {
            $title = 'Débit validé par la boutique';
            $message = $boutiqueNom . ' a validé le débit de ' . $montant . ' (' . $validation->source_label . '). Le solde a été débité.';
        } else {
            $title = 'Débit contesté par la boutique';
            $message = $boutiqueNom . ' a contesté le débit de ' . $montant . ' (' . $validation->source_label . '). Aucun débit appliqué.';
        }

        $actionUrl = $validation->source_type === 'achat' && $validation->source_id
            ? route('admin.achats.show', $validation->source_id)
            : route('admin.dashboard');

        $admin->notify(new AdminValidationNotification($title, $message, 'Voir', $actionUrl));
    }
}
