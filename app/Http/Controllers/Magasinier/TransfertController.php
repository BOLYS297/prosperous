<?php

namespace App\Http\Controllers\Magasinier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DemandeTransfert;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\StockShippedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TransfertController extends Controller
{
    public function index()
    {
        $magasinId = Auth::user()->boutique_id;

        $demandes = DemandeTransfert::with(['produit', 'boutique'])
            ->orderByRaw("FIELD(statut, 'en_attente', 'probleme', 'expediee', 'livree')")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('magasinier.transferts.index', compact('demandes'));
    }

    /** Le magasinier refuse une demande de stock : aucun mouvement de stock. */
    public function rejeter(Request $request, $id)
    {
        $request->validate([
            'note_probleme' => 'nullable|string|max:500',
        ]);

        $alreadyProcessed = false;

        DB::transaction(function () use ($id, $request, &$alreadyProcessed) {
            // Verrou + vérification atomique : une demande ne peut être traitée
            // qu'une seule fois (pas de refus après expédition).
            $demande = DemandeTransfert::where('id', $id)->lockForUpdate()->first();

            if (! $demande || $demande->statut !== 'en_attente') {
                $alreadyProcessed = true;
                return;
            }

            $demande->update([
                'statut' => 'refusee',
                'note_probleme' => $request->input('note_probleme') ?: 'Demande refusée par le magasin.',
            ]);
        });

        if ($alreadyProcessed) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $demande = DemandeTransfert::with(['produit', 'boutique'])->find($id);

        $boutiquiers = User::where('boutique_id', $demande->boutique_id)
            ->where('role', 'boutiquier')
            ->get();

        if ($boutiquiers->isNotEmpty()) {
            Notification::send($boutiquiers, new \App\Notifications\PendingActionNotification(
                'Demande de stock refusée',
                "Votre demande de {$demande->quantite_demandee} × {$demande->produit?->nom} a été refusée par le magasin. Motif : {$demande->note_probleme}",
                'Voir mes demandes',
                route('boutiquier.transferts.index'),
                ['type' => 'demande_refusee']
            ));
        }

        return back()->with('success', 'Demande refusée. La boutique a été notifiée.');
    }

    public function expedier(Request $request, $id)
    {
        $request->validate([
            'quantite_expediee' => 'required|integer|min:1',
        ]);

        $demande = DemandeTransfert::findOrFail($id);
        $magasinId = Auth::user()->boutique_id;

        $alreadyProcessed = false;

        try {
            DB::transaction(function () use ($id, $demande, $request, $magasinId, &$alreadyProcessed) {
                // Verrou + vérification atomique : une demande ne peut être
                // expédiée (et déstocker le magasin) qu'une seule fois.
                $fresh = DemandeTransfert::where('id', $id)->lockForUpdate()->first();
                if (! $fresh || $fresh->statut !== 'en_attente') {
                    $alreadyProcessed = true;
                    return;
                }

                // Sortie du stock du magasin (FIFO) et CAPTURE des prix des lots
                // consommés : ils sont figés sur la demande pour recréer le lot à
                // l'identique dans la boutique à la réception (coût préservé).
                $consumed = \App\Models\Stock::consumeForSale($magasinId, $fresh->produit_id, $request->quantite_expediee, null);

                $produit = \App\Models\Produit::find($fresh->produit_id);
                $defaultCost = ($produit && (float) $produit->getRawOriginal('prix_achat') > 0)
                    ? (float) $produit->getRawOriginal('prix_achat')
                    : null;

                $totalCost = 0;
                $totalQty = 0;
                $coutConnu = true;
                $prixVente = null;
                $prixGrossiste = null;

                foreach ($consumed as $c) {
                    $lot = $c['stock'];
                    $q = (int) $c['quantite'];
                    // Lot sans coût (inventaire d'avant le suivi des coûts) : repli
                    // sur le prix d'achat par défaut du produit.
                    $lotCost = $lot->prix_achat_unitaire ?? $defaultCost;
                    if ($lotCost === null) {
                        $coutConnu = false;
                    }
                    $totalCost += ((float) ($lotCost ?? 0)) * $q;
                    $totalQty += $q;

                    if ($prixVente === null && $lot->prix_vente_unitaire !== null) {
                        $prixVente = (float) $lot->prix_vente_unitaire;
                    }
                    if ($prixGrossiste === null && $lot->prix_vente_grossiste_unitaire !== null) {
                        $prixGrossiste = (float) $lot->prix_vente_grossiste_unitaire;
                    }
                }

                $fresh->update([
                    'quantite_expediee' => $request->quantite_expediee,
                    'prix_achat_unitaire' => ($coutConnu && $totalQty > 0) ? $totalCost / $totalQty : null,
                    'prix_vente_unitaire' => $prixVente,
                    'prix_vente_grossiste_unitaire' => $prixGrossiste,
                    'statut' => 'expediee',
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Stock insuffisant dans le magasin pour expédier cette quantité.');
        }

        if ($alreadyProcessed) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $demande->refresh()->load(['produit', 'boutique']);

        $boutiquiers = User::where('boutique_id', $demande->boutique_id)
            ->where('role', 'boutiquier')
            ->whereNotNull('email')
            ->get();

        if ($boutiquiers->isNotEmpty()) {
            Notification::send($boutiquiers, new StockShippedNotification(
                $demande->produit->nom,
                $demande->quantite_demandee,
                $request->quantite_expediee,
                $demande->boutique->nom,
                route('boutiquier.transferts.index')
            ));
        }

        return back()->with('success', 'Produits expédiés vers la boutique.');
    }
}
