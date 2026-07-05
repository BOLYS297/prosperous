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

    public function expedier(Request $request, $id)
    {
        $request->validate([
            'quantite_expediee' => 'required|integer|min:1',
        ]);

        $demande = DemandeTransfert::findOrFail($id);
        $magasinId = Auth::user()->boutique_id;

        if ($demande->statut !== 'en_attente') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        try {
            DB::transaction(function () use ($demande, $request, $magasinId) {
                \App\Models\Stock::consumeForSale($magasinId, $demande->produit_id, $request->quantite_expediee, null);

                $demande->update([
                    'quantite_expediee' => $request->quantite_expediee,
                    'statut' => 'expediee',
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Stock insuffisant dans le magasin pour expédier cette quantité.');
        }

        $demande->load(['produit', 'boutique']);

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
