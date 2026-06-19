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

        return view('boutiquier.transferts.index', compact('demandes'));
    }

    public function create()
    {
        $produits = Produit::orderBy('nom')->get();
        return view('boutiquier.transferts.create', compact('produits'));
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

    public function confirmer(Request $request, $id)
    {
        $demande = DemandeTransfert::where('boutique_id', Auth::user()->boutique_id)->findOrFail($id);

        if ($demande->statut !== 'expediee') {
            return back()->with('error', 'Vous ne pouvez confirmer qu\'une demande expédiée.');
        }

        DB::transaction(function () use ($demande) {
            $demande->update(['statut' => 'livree']);

            // Ajouter le stock à la boutique
            $stock = Stock::firstOrCreate(
                ['boutique_id' => $demande->boutique_id, 'produit_id' => $demande->produit_id],
                ['quantite' => 0]
            );
            $stock->increment('quantite', $demande->quantite_expediee);
        });

        return back()->with('success', 'Réception confirmée ! Le stock a été ajouté à votre boutique.');
    }


    public function signalerProbleme(Request $request, $id)
    {
        $request->validate([
            'quantite_recue' => 'required|integer|min:0',
            'note_probleme' => 'required|string|max:500',
        ]);

        $demande = DemandeTransfert::where('boutique_id', Auth::user()->boutique_id)->findOrFail($id);

        if ($demande->statut !== 'expediee') {
            return back()->with('error', 'Vous ne pouvez signaler un problème que sur une demande expédiée.');
        }

        if ($request->quantite_recue > $demande->quantite_expediee) {
            return back()->with('error', 'La quantité reçue ne peut pas être supérieure à la quantité expédiée.');
        }

        $quantiteRecue = $request->quantite_recue;
        $quantiteManquante = $demande->quantite_expediee - $quantiteRecue;

        DB::transaction(function () use ($demande, $quantiteRecue, $request) {
            $demande->update([
                'statut' => 'probleme',
                'note_probleme' => $request->note_probleme,
                'quantite_recue' => $quantiteRecue,
            ]);

            $stock = Stock::firstOrCreate(
                ['boutique_id' => $demande->boutique_id, 'produit_id' => $demande->produit_id],
                ['quantite' => 0]
            );
            $stock->increment('quantite', $quantiteRecue);
        });

        $demande->load(['produit', 'boutique']);

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
