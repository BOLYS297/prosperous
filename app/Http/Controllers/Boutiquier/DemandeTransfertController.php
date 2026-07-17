<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DemandeTransfert;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\AdminValidationNotification;
use App\Notifications\StockRequestNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DemandeTransfertController extends Controller
{
    public function index()
    {
        $boutiqueId = Auth::user()->boutique_id;
        $demandes = DemandeTransfert::with('produit')
            ->where('boutique_id', $boutiqueId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Transferts entre points de vente initiés par le magasin :
        //  - à autoriser  : ma boutique est la SOURCE (le stock va sortir)
        //  - à réceptionner : ma boutique est la DESTINATION (le stock arrive)
        $aAutoriser = \App\Models\TransfertStock::with(['produit', 'destination', 'initiator'])
            ->aAutoriser($boutiqueId)
            ->orderBy('created_at')
            ->get();

        $aReceptionner = \App\Models\TransfertStock::with(['produit', 'source'])
            ->aReceptionner($boutiqueId)
            ->orderBy('created_at')
            ->get();

        // Stock disponible par produit dans MA boutique (plafonne l'autorisation).
        $stockDispo = \App\Models\Stock::where('boutique_id', $boutiqueId)
            ->selectRaw('produit_id, SUM(quantite) as total')
            ->groupBy('produit_id')
            ->pluck('total', 'produit_id');

        return view('boutiquier.transferts.index', compact('demandes', 'aAutoriser', 'aReceptionner', 'stockDispo'));
    }

    public function create()
    {
        $produits = Produit::orderBy('nom')->get();

        // Stock disponible au(x) magasin(s) central(aux), agrégé par produit,
        // pour éviter de demander plus que ce que le magasin possède.
        $magasinIds = \App\Models\Boutique::where('type', 'magasin')->pluck('id');
        $stockMagasin = Stock::whereIn('boutique_id', $magasinIds)
            ->selectRaw('produit_id, SUM(quantite) as total')
            ->groupBy('produit_id')
            ->pluck('total', 'produit_id');

        return view('boutiquier.transferts.create', compact('produits', 'stockMagasin'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'quantite_demandee' => 'required|integer|min:1',
        ]);

        $demande = DemandeTransfert::create([
            'boutique_id' => Auth::user()->boutique_id,
            'produit_id' => $request->produit_id,
            'quantite_demandee' => $request->quantite_demandee,
            'statut' => 'en_attente',
        ]);

        $demande->load(['produit', 'boutique']);

        $magasiniers = User::where('role', 'magasinier')
            ->whereNotNull('email')
            ->get();

        if ($magasiniers->isNotEmpty()) {
            Notification::send($magasiniers, new StockRequestNotification(
                $demande->produit->nom,
                $demande->quantite_demandee,
                $demande->boutique->nom,
                route('magasinier.transferts.index')
            ));
        }

        return redirect()->route('boutiquier.transferts.index')->with('success', 'Demande de stock envoyée au magasin central.');
    }

    /** Modifier sa demande tant que le magasin ne l'a pas traitée. */
    public function edit($id)
    {
        $demande = DemandeTransfert::where('boutique_id', Auth::user()->boutique_id)->findOrFail($id);

        if ($demande->statut !== 'en_attente') {
            return redirect()->route('boutiquier.transferts.index')->with('error', $this->messageVerrou());
        }

        $produits = Produit::orderBy('nom')->get();

        $magasinIds = \App\Models\Boutique::where('type', 'magasin')->pluck('id');
        $stockMagasin = Stock::whereIn('boutique_id', $magasinIds)
            ->selectRaw('produit_id, SUM(quantite) as total')
            ->groupBy('produit_id')
            ->pluck('total', 'produit_id');

        return view('boutiquier.transferts.edit', compact('demande', 'produits', 'stockMagasin'));
    }

    public function update(Request $request, $id)
    {
        $demande = DemandeTransfert::where('boutique_id', Auth::user()->boutique_id)->findOrFail($id);

        if ($demande->statut !== 'en_attente') {
            return redirect()->route('boutiquier.transferts.index')->with('error', $this->messageVerrou());
        }

        $validated = $request->validate([
            'produit_id' => 'required|exists:produits,id',
            'quantite_demandee' => 'required|integer|min:1',
        ]);

        $demande->update($validated);

        return redirect()->route('boutiquier.transferts.index')->with('success', 'Demande de stock mise à jour.');
    }

    public function destroy($id)
    {
        $demande = DemandeTransfert::where('boutique_id', Auth::user()->boutique_id)->findOrFail($id);

        if ($demande->statut !== 'en_attente') {
            return redirect()->route('boutiquier.transferts.index')->with('error', $this->messageVerrou());
        }

        $demande->delete();

        return redirect()->route('boutiquier.transferts.index')->with('success', 'Demande de stock supprimée.');
    }

    protected function messageVerrou(): string
    {
        return "Cette demande a déjà été traitée par le magasin : elle n'est plus modifiable.";
    }

    public function confirmer(Request $request, $id)
    {
        $boutiqueId = Auth::user()->boutique_id;
        $alreadyProcessed = false;

        DB::transaction(function () use ($id, $boutiqueId, &$alreadyProcessed) {
            // Verrou + vérification atomique : le stock n'est ajouté à la boutique
            // qu'une seule fois, même en cas de double-clic ou de rejeu hors-ligne.
            $demande = DemandeTransfert::where('boutique_id', $boutiqueId)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $demande || $demande->statut !== 'expediee') {
                $alreadyProcessed = true;
                return;
            }

            $demande->update(['statut' => 'livree']);

            // Entrée en stock au PRIX D'ORIGINE figé à l'expédition (coût préservé),
            // via un nouveau lot plutôt qu'une fusion sans coût.
            Stock::addBatch(
                $demande->boutique_id,
                $demande->produit_id,
                $demande->quantite_expediee,
                $demande->prix_achat_unitaire !== null ? (float) $demande->prix_achat_unitaire : null,
                $demande->prix_vente_unitaire !== null ? (float) $demande->prix_vente_unitaire : null,
                $demande->prix_vente_grossiste_unitaire !== null ? (float) $demande->prix_vente_grossiste_unitaire : null,
                'transfert',
                $demande->id
            );
        });

        if ($alreadyProcessed) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        return back()->with('success', 'Réception confirmée ! Le stock a été ajouté à votre boutique.');
    }


    public function signalerProbleme(Request $request, $id)
    {
        $request->validate([
            'quantite_recue' => 'required|integer|min:0',
            'note_probleme' => 'required|string|max:500',
        ]);

        $boutiqueId = Auth::user()->boutique_id;
        $demande = DemandeTransfert::where('boutique_id', $boutiqueId)->findOrFail($id);

        if ($demande->statut !== 'expediee') {
            return back()->with('error', 'Vous ne pouvez signaler un problème que sur une demande expédiée.');
        }

        if ($request->quantite_recue > $demande->quantite_expediee) {
            return back()->with('error', 'La quantité reçue ne peut pas être supérieure à la quantité expédiée.');
        }

        $quantiteRecue = (int) $request->quantite_recue;
        $quantiteManquante = $demande->quantite_expediee - $quantiteRecue;
        $alreadyProcessed = false;

        DB::transaction(function () use ($id, $boutiqueId, $quantiteRecue, $request, &$alreadyProcessed) {
            $fresh = DemandeTransfert::where('boutique_id', $boutiqueId)
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (! $fresh || $fresh->statut !== 'expediee') {
                $alreadyProcessed = true;
                return;
            }

            $fresh->update([
                'statut' => 'probleme',
                'note_probleme' => $request->note_probleme,
                'quantite_recue' => $quantiteRecue,
            ]);

            // Seule la quantité RÉELLEMENT reçue entre en stock, au prix figé à
            // l'expédition (l'écart manquant est signalé à l'admin, pas stocké).
            if ($quantiteRecue > 0) {
                Stock::addBatch(
                    $fresh->boutique_id,
                    $fresh->produit_id,
                    $quantiteRecue,
                    $fresh->prix_achat_unitaire !== null ? (float) $fresh->prix_achat_unitaire : null,
                    $fresh->prix_vente_unitaire !== null ? (float) $fresh->prix_vente_unitaire : null,
                    $fresh->prix_vente_grossiste_unitaire !== null ? (float) $fresh->prix_vente_grossiste_unitaire : null,
                    'transfert',
                    $fresh->id
                );
            }
        });

        if ($alreadyProcessed) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $demande->refresh()->load(['produit', 'boutique']);

        $admins = User::whereIn('role', ['admin', 'super_admin'])
            ->whereNotNull('email')
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AdminValidationNotification(
                'Problème sur une livraison de transfert',
                "La boutique {$demande->boutique->nom} a reçu {$quantiteRecue} unité(s) sur {$demande->quantite_expediee} de {$demande->produit->nom}. Manquant : {$quantiteManquante} unité(s). Message : {$request->note_probleme}",
                'Voir le tableau de bord',
                route('admin.dashboard')
            ));
        }

        return back()->with('success', 'Problème signalé au magasin central et stock reçu enregistré.');
    }
}
