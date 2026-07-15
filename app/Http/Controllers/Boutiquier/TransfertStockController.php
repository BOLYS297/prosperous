<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\TransfertStock;
use App\Models\User;
use App\Notifications\AdminValidationNotification;
use App\Notifications\PendingActionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TransfertStockController extends Controller
{
    /**
     * Le vendeur de la boutique SOURCE autorise (tout ou partie) la quantité à
     * transférer : le stock sort immédiatement de sa boutique (FIFO) et part
     * "en transit" vers la destination.
     */
    public function autoriser(Request $request, TransfertStock $transfert)
    {
        $request->validate([
            'quantite_autorisee' => 'required|integer|min:1',
        ]);

        $boutiqueId = (int) Auth::user()->boutique_id;
        $quantite = (int) $request->input('quantite_autorisee');

        $alreadyProcessed = false;
        $tropGrand = false;

        try {
            DB::transaction(function () use ($transfert, $boutiqueId, $quantite, &$alreadyProcessed, &$tropGrand) {
                // Verrou + vérification atomique : un transfert ne sort du stock
                // qu'une seule fois (double-clic / rejeu hors-ligne).
                $fresh = TransfertStock::where('id', $transfert->id)->lockForUpdate()->first();

                if (! $fresh || $fresh->statut !== 'en_attente_source' || (int) $fresh->source_boutique_id !== $boutiqueId) {
                    $alreadyProcessed = true;
                    return;
                }

                if ($quantite > $fresh->quantite_demandee) {
                    $tropGrand = true;
                    return;
                }

                // Sortie du stock de la boutique source (FIFO) — lève une
                // RuntimeException si le stock est insuffisant.
                $consumed = Stock::consumeForSale($fresh->source_boutique_id, $fresh->produit_id, $quantite, null);

                // On fige les prix des lots consommés pour les recréer à l'arrivée.
                $totalCost = 0;
                $totalQty = 0;
                $prixVente = null;
                $prixGrossiste = null;

                foreach ($consumed as $c) {
                    $lot = $c['stock'];
                    $q = (int) $c['quantite'];
                    $totalCost += ((float) ($lot->prix_achat_unitaire ?? 0)) * $q;
                    $totalQty += $q;

                    if ($prixVente === null && $lot->prix_vente_unitaire !== null) {
                        $prixVente = (float) $lot->prix_vente_unitaire;
                    }
                    if ($prixGrossiste === null && $lot->prix_vente_grossiste_unitaire !== null) {
                        $prixGrossiste = (float) $lot->prix_vente_grossiste_unitaire;
                    }
                }

                $fresh->update([
                    'quantite_autorisee' => $quantite,
                    'prix_achat_unitaire' => $totalQty > 0 ? $totalCost / $totalQty : null,
                    'prix_vente_unitaire' => $prixVente,
                    'prix_vente_grossiste_unitaire' => $prixGrossiste,
                    'statut' => 'autorise',
                    'source_user_id' => Auth::id(),
                    'authorized_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Stock insuffisant dans votre boutique pour autoriser cette quantité.');
        }

        if ($alreadyProcessed) {
            return back()->with('error', 'Ce transfert a déjà été traité.');
        }
        if ($tropGrand) {
            return back()->with('error', 'La quantité autorisée ne peut pas dépasser la quantité demandée.');
        }

        $transfert->refresh()->load(['produit', 'source', 'destination']);

        $this->notifyBoutiquiers(
            $transfert->destination_boutique_id,
            'Transfert à réceptionner',
            "{$transfert->source->nom} a envoyé {$transfert->quantite_autorisee} × {$transfert->produit->nom}. Confirmez la quantité reçue.",
            'Réceptionner',
            route('boutiquier.transferts.index')
        );

        return back()->with('success', 'Transfert autorisé : le stock a quitté votre boutique et attend la réception.');
    }

    /** Le vendeur source refuse le transfert : aucun mouvement de stock. */
    public function refuser(Request $request, TransfertStock $transfert)
    {
        $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $boutiqueId = (int) Auth::user()->boutique_id;
        $alreadyProcessed = false;

        DB::transaction(function () use ($transfert, $boutiqueId, $request, &$alreadyProcessed) {
            $fresh = TransfertStock::where('id', $transfert->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->statut !== 'en_attente_source' || (int) $fresh->source_boutique_id !== $boutiqueId) {
                $alreadyProcessed = true;
                return;
            }

            $fresh->update([
                'statut' => 'refuse',
                'note' => $request->input('note'),
                'source_user_id' => Auth::id(),
            ]);
        });

        if ($alreadyProcessed) {
            return back()->with('error', 'Ce transfert a déjà été traité.');
        }

        $this->notifyAdminsEtMagasiniers(
            'Transfert de stock refusé',
            "Le transfert #{$transfert->id} a été refusé par la boutique source." . ($request->input('note') ? " Motif : {$request->input('note')}" : '')
        );

        return back()->with('success', 'Transfert refusé. Le magasin a été notifié.');
    }

    /**
     * Le vendeur de la boutique DESTINATION confirme la quantité reçue :
     * le stock entre dans sa boutique, au prix d'origine.
     */
    public function receptionner(Request $request, TransfertStock $transfert)
    {
        $request->validate([
            'quantite_recue' => 'required|integer|min:0',
        ]);

        $boutiqueId = (int) Auth::user()->boutique_id;
        $quantite = (int) $request->input('quantite_recue');

        $alreadyProcessed = false;
        $tropGrand = false;
        $ecart = 0;

        DB::transaction(function () use ($transfert, $boutiqueId, $quantite, &$alreadyProcessed, &$tropGrand, &$ecart) {
            $fresh = TransfertStock::where('id', $transfert->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->statut !== 'autorise' || (int) $fresh->destination_boutique_id !== $boutiqueId) {
                $alreadyProcessed = true;
                return;
            }

            if ($quantite > $fresh->quantite_autorisee) {
                $tropGrand = true;
                return;
            }

            if ($quantite > 0) {
                // Entrée en stock au prix d'origine (coût préservé).
                Stock::addBatch(
                    $fresh->destination_boutique_id,
                    $fresh->produit_id,
                    $quantite,
                    $fresh->prix_achat_unitaire !== null ? (float) $fresh->prix_achat_unitaire : null,
                    $fresh->prix_vente_unitaire !== null ? (float) $fresh->prix_vente_unitaire : null,
                    $fresh->prix_vente_grossiste_unitaire !== null ? (float) $fresh->prix_vente_grossiste_unitaire : null,
                    'transfert',
                    $fresh->id
                );
            }

            $ecart = (int) $fresh->quantite_autorisee - $quantite;

            $fresh->update([
                'quantite_recue' => $quantite,
                'statut' => $ecart === 0 ? 'recu' : 'probleme',
                'destination_user_id' => Auth::id(),
                'received_at' => now(),
            ]);
        });

        if ($alreadyProcessed) {
            return back()->with('error', 'Ce transfert a déjà été traité.');
        }
        if ($tropGrand) {
            return back()->with('error', 'La quantité reçue ne peut pas dépasser la quantité envoyée.');
        }

        if ($ecart > 0) {
            $transfert->refresh()->load(['produit', 'source', 'destination']);
            $this->notifyAdminsEtMagasiniers(
                'Écart sur un transfert de stock',
                "Transfert #{$transfert->id} ({$transfert->produit->nom}) : {$transfert->quantite_recue} reçu(s) sur {$transfert->quantite_autorisee} envoyé(s) de {$transfert->source->nom} vers {$transfert->destination->nom}. Manquant : {$ecart}."
            );

            return back()->with('success', 'Réception enregistrée avec un écart. Le magasin et l\'administrateur ont été notifiés.');
        }

        return back()->with('success', 'Réception confirmée : le stock a été ajouté à votre boutique.');
    }

    protected function notifyBoutiquiers(int $boutiqueId, string $title, string $message, string $actionLabel, string $actionUrl): void
    {
        $recipients = User::where('role', 'boutiquier')->where('boutique_id', $boutiqueId)->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PendingActionNotification(
            $title,
            $message,
            $actionLabel,
            $actionUrl,
            ['type' => 'transfert_stock']
        ));
    }

    protected function notifyAdminsEtMagasiniers(string $title, string $message): void
    {
        $recipients = User::whereIn('role', ['admin', 'super_admin', 'magasinier'])->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AdminValidationNotification(
            $title,
            $message,
            'Voir les transferts',
            route('magasinier.transferts-stock.index')
        ));
    }
}
