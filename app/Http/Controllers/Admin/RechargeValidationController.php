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
        if (! in_array($recharge->statut, ['confirmee_par_magasinier', 'anomalie'])) {
            return redirect()->route('admin.recharges.validation.index')->with('error', 'Cette recharge a déjà été traitée.');
        }

        $recharge->load('lignes');

        \Illuminate\Support\Facades\Log::info('VALIDE_START', ['recharge_id' => $recharge->id, 'statut_before' => $recharge->statut]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($recharge) {
            // Si c'est une anomalie, s'assurer que les lignes ont bien une valeur de quantite_recue
            if ($recharge->statut === 'anomalie') {
                foreach ($recharge->lignes as $ligne) {
                    $recue = $ligne->quantite_recue ?? 0;
                    $ligne->update([
                        'quantite_recue' => $recue,
                        'quantite_manquante' => max(0, $ligne->quantite_envoyee - $recue),
                    ]);
                }
                // En cas d'anomalie approuvée, on enregistre uniquement les quantités réellement reçues
                $this->updateStockForRecharge($recharge, false);
            } else {
                // Cas normal: enregistrer les quantités reçues transmises par le magasinier
                $this->updateStockForRecharge($recharge, false);
            }

            $recharge->update(['statut' => 'approuvee']);
            \Illuminate\Support\Facades\Log::info('VALIDE_UPDATED', ['recharge_id' => $recharge->id, 'statut_after' => 'approuvee']);
        });

        $this->notifyBoutiqueForRecharge($recharge, 'Recharge approuvée', 'La recharge a été approuvée par l’administrateur et le stock a été mis à jour.', 'Voir la recharge', route('admin.recharges.validation.show', $recharge));

        // Fallback: ensure statut persisted
        \Illuminate\Support\Facades\DB::table('recharges')->where('id', $recharge->id)->update(['statut' => 'approuvee']);

        return redirect()->route('admin.recharges.validation.index')->with('success', 'Recharge approuvée et stock mis à jour.');
    }

    public function rejeter(\App\Models\Recharge $recharge)
    {
        if (! in_array($recharge->statut, ['confirmee_par_magasinier', 'anomalie'])) {
            return redirect()->route('admin.recharges.validation.index')->with('error', 'Cette recharge a déjà été traitée.');
        }

        $recharge->load('lignes');

        \Illuminate\Support\Facades\DB::transaction(function () use ($recharge) {
            // Si l'anomalie est rejetée, on force la quantité reçue = quantité envoyée
            // et on annule la dette fournisseur (quantite_manquante = 0).
            if ($recharge->statut === 'anomalie') {
                foreach ($recharge->lignes as $ligne) {
                    $ligne->update([
                        'quantite_recue' => $ligne->quantite_envoyee,
                        'quantite_manquante' => 0,
                    ]);
                }

                // Enregistrer le stock basé sur les quantités marquées reçues
                $this->updateStockForRecharge($recharge, false);
            } else {
                // Cas non-anomalie: enregistrer la quantité envoyée complète
                $this->updateStockForRecharge($recharge, true);
            }

            $recharge->update([
                'statut' => 'rejetee',
                'raison_rejet' => null,
            ]);
        });

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
                $stock = \App\Models\Stock::firstOrCreate(
                    ['boutique_id' => $recharge->destination_id, 'produit_id' => $ligne->produit_id],
                    ['quantite' => 0]
                );
                $stock->increment('quantite', $quantite);
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
