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
        $this->authorizeValidation($validation);

        DB::transaction(function () use ($validation) {
            $boutique = $validation->boutique()->lockForUpdate()->first();
            $boutique->decrement('solde', $validation->amount);

            if ($validation->source_type === 'achat') {
                // Enregistrer le paiement (l'achat devient réellement payé).
                AchatPaiement::create([
                    'achat_id' => $validation->source_id,
                    'boutique_id' => $validation->boutique_id,
                    'user_id' => Auth::id(),
                    'montant' => $validation->amount,
                    'description' => 'Paiement comptant validé pour l\'achat #' . $validation->source_id,
                ]);
            } elseif ($validation->source_type === 'depense') {
                $depense = Depense::find($validation->source_id);
                if ($depense) {
                    $depense->update([
                        'statut' => 'approved',
                        'validated_at' => now(),
                    ]);
                }
            } elseif ($validation->source_type === 'recette') {
                // La recette quitte la caisse de la boutique et entre dans le
                // solde personnel de l'administrateur qui l'a demandée.
                \App\Models\AdminSoldeMouvement::enregistrer(
                    $validation->initiator_id,
                    'recette',
                    (float) $validation->amount,
                    $validation->motif ?: 'Récupération des recettes',
                    $validation->boutique_id,
                    'recette',
                    $validation->id
                );
            }

            $validation->update([
                'status' => 'confirmed',
                'responder_id' => Auth::id(),
                'responded_at' => now(),
            ]);
        });

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
        $this->authorizeValidation($validation);

        DB::transaction(function () use ($validation) {
            if ($validation->source_type === 'depense') {
                $depense = Depense::find($validation->source_id);
                if ($depense) {
                    $depense->update([
                        'statut' => 'rejected',
                        'rejet_reason' => 'Débit contesté par la boutique.',
                    ]);
                }
            }

            $validation->update([
                'status' => 'rejected',
                'responder_id' => Auth::id(),
                'responded_at' => now(),
            ]);
        });

        $this->notifyAdmin($validation, false);

        LogActivite::create([
            'user_id' => Auth::id(),
            'action' => 'boutiquier.debit.dispute',
            'description' => 'Débit contesté (' . $validation->source_label . ') : ' . money_format_app($validation->amount) . '.',
        ]);

        return back()->with('success', 'Débit contesté. L\'administrateur a été notifié ; votre solde n\'a pas été débité.');
    }

    protected function authorizeValidation(DebitValidation $validation): void
    {
        if ($validation->boutique_id !== Auth::user()->boutique_id) {
            abort(403, 'Ce débit ne concerne pas votre boutique.');
        }

        if (! $validation->isPending()) {
            abort(409, 'Ce débit a déjà été traité.');
        }
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
