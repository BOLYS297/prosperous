<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RechargeStatusNotification;

class RechargeValidationController extends Controller
{
    public function index()
    {
        $recharges = \App\Models\Recharge::with(['fournisseur', 'lignes.produit', 'destination'])
            ->whereIn('statut', ['confirmee_par_magasinier', 'anomalie'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.recharges.validation-list', compact('recharges'));
    }

    public function show(\App\Models\Recharge $recharge)
    {
        $recharge->load(['fournisseur', 'lignes.produit', 'destination', 'justificatifs']);
        return view('admin.recharges.validation-show', compact('recharge'));
    }

    public function valider(Request $request, \App\Models\Recharge $recharge)
    {
        $alreadyProcessed = false;

        \Illuminate\Support\Facades\DB::transaction(function () use ($recharge, &$alreadyProcessed) {
            // Verrou + vérification atomique : le stock n'est ajouté qu'une seule
            // fois, même en cas de double-clic ou de double soumission.
            $fresh = \App\Models\Recharge::where('id', $recharge->id)->lockForUpdate()->first();
            if (! $fresh || ! in_array($fresh->statut, ['confirmee_par_magasinier', 'anomalie'])) {
                $alreadyProcessed = true;
                return;
            }

            $fresh->load('lignes');

            // En cas d'anomalie, s'assurer que les lignes ont une quantite_recue cohérente.
            if ($fresh->statut === 'anomalie') {
                foreach ($fresh->lignes as $ligne) {
                    $recue = $ligne->quantite_recue ?? 0;
                    $ligne->update([
                        'quantite_recue' => $recue,
                        'quantite_manquante' => max(0, $ligne->quantite_envoyee - $recue),
                    ]);
                }
            }

            // On enregistre les quantités réellement reçues.
            $this->updateStockForRecharge($fresh, false);

            $fresh->update(['statut' => 'approuvee']);
        });

        if ($alreadyProcessed) {
            return redirect()->route('admin.recharges.validation.index')->with('error', 'Cette recharge a déjà été traitée.');
        }

        $recharge->refresh();
        $this->notifyBoutiqueForRecharge($recharge, 'Recharge approuvée', 'La recharge a été approuvée par l’administrateur et le stock a été mis à jour.', 'Voir la recharge', route('admin.recharges.validation.show', $recharge));

        return redirect()->route('admin.recharges.validation.index')->with('success', 'Recharge approuvée et stock mis à jour.');
    }

    public function rejeter(\App\Models\Recharge $recharge)
    {
        $alreadyProcessed = false;

        \Illuminate\Support\Facades\DB::transaction(function () use ($recharge, &$alreadyProcessed) {
            $fresh = \App\Models\Recharge::where('id', $recharge->id)->lockForUpdate()->first();
            if (! $fresh || ! in_array($fresh->statut, ['confirmee_par_magasinier', 'anomalie'])) {
                $alreadyProcessed = true;
                return;
            }

            $fresh->load('lignes');

            // Si l'anomalie est rejetée, on force la quantité reçue = quantité envoyée
            // et on annule la dette fournisseur (quantite_manquante = 0).
            if ($fresh->statut === 'anomalie') {
                foreach ($fresh->lignes as $ligne) {
                    $ligne->update([
                        'quantite_recue' => $ligne->quantite_envoyee,
                        'quantite_manquante' => 0,
                    ]);
                }

                $this->updateStockForRecharge($fresh, false);
            } else {
                // Cas non-anomalie : enregistrer la quantité envoyée complète.
                $this->updateStockForRecharge($fresh, true);
            }

            $fresh->update([
                'statut' => 'rejetee',
                'raison_rejet' => null,
            ]);
        });

        if ($alreadyProcessed) {
            return redirect()->route('admin.recharges.validation.index')->with('error', 'Cette recharge a déjà été traitée.');
        }

        $recharge->refresh();
        $this->notifyBoutiqueForRecharge($recharge, 'Recharge rejetée', 'La recharge a été rejetée par l’administrateur et le stock a été enregistré selon les quantités reçues.', 'Voir la recharge', route('admin.recharges.validation.show', $recharge));

        return redirect()->route('admin.recharges.validation.index')->with('success', 'Recharge rejetée et stock enregistrée.');
    }

    protected function updateStockForRecharge(\App\Models\Recharge $recharge, $useFullQuantity = false)
    {
        foreach ($recharge->lignes as $ligne) {
            // Si rejet: utiliser la quantité envoyée complète
            // Si approbation: utiliser la quantité reçue
            $quantite = $useFullQuantity ? $ligne->quantite_envoyee : $ligne->quantite_recue;

            if ($quantite > 0) {
                $produit = \App\Models\Produit::find($ligne->produit_id);
                $achatLigne = null;
                if ($recharge->achat_id) {
                    $achatLigne = \App\Models\AchatLigne::where('achat_id', $recharge->achat_id)
                        ->where('produit_id', $ligne->produit_id)
                        ->first();
                }

                \App\Models\Stock::addBatch(
                    $recharge->destination_id,
                    $ligne->produit_id,
                    $quantite,
                    $achatLigne?->prix_unitaire ?? $produit?->prix_achat ?? 0,
                    $achatLigne?->prix_vente ?? $produit?->prix_vente ?? $achatLigne?->prix_unitaire ?? 0,
                    $achatLigne?->prix_vente_grossiste,
                    'recharge',
                    $recharge->id
                );
            }
        }
    }

    protected function notifyBoutiqueForRecharge($recharge, string $title, string $message, string $actionLabel, string $actionUrl)
    {
        $boutique = $recharge->destination;
        if (! $boutique) {
            return;
        }

        $boutiqueUsers = \App\Models\User::where('boutique_id', $boutique->id)
            ->where('role', 'boutiquier')
            ->get();

        if ($boutiqueUsers->isEmpty()) {
            return;
        }

        Notification::send($boutiqueUsers, new RechargeStatusNotification(
            $title,
            $message,
            $actionLabel,
            $actionUrl,
        ));
    }
}
