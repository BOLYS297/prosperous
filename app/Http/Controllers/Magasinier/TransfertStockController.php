<?php

namespace App\Http\Controllers\Magasinier;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\TransfertStock;
use App\Models\User;
use App\Notifications\PendingActionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class TransfertStockController extends Controller
{
    public function index()
    {
        $transferts = TransfertStock::with(['source', 'destination', 'produit', 'initiator'])
            ->orderByRaw("FIELD(statut,'en_attente_source','autorise','probleme','recu','refuse')")
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('magasinier.transferts-stock.index', compact('transferts'));
    }

    public function create()
    {
        $boutiques = Boutique::orderBy('type')->orderBy('nom')->get();
        $produits = Produit::orderBy('nom')->get();

        // Stock disponible par boutique puis par produit (pour l'affichage dynamique).
        $stocks = Stock::selectRaw('boutique_id, produit_id, SUM(quantite) as total')
            ->groupBy('boutique_id', 'produit_id')
            ->get()
            ->groupBy('boutique_id')
            ->map(fn ($rows) => $rows->pluck('total', 'produit_id'));

        return view('magasinier.transferts-stock.create', compact('boutiques', 'produits', 'stocks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_boutique_id' => 'required|exists:boutiques,id|different:destination_boutique_id',
            'destination_boutique_id' => 'required|exists:boutiques,id',
            'produit_id' => 'required|exists:produits,id',
            'quantite_demandee' => 'required|integer|min:1',
        ], [
            'source_boutique_id.different' => 'La source et la destination doivent être deux points de vente différents.',
        ]);

        $dispo = Stock::totalFor((int) $validated['source_boutique_id'], (int) $validated['produit_id']);
        if ((int) $validated['quantite_demandee'] > $dispo) {
            return back()->withInput()->with('error', "Le point de vente source ne dispose que de {$dispo} unité(s) de ce produit.");
        }

        $transfert = TransfertStock::create([
            'source_boutique_id' => $validated['source_boutique_id'],
            'destination_boutique_id' => $validated['destination_boutique_id'],
            'produit_id' => $validated['produit_id'],
            'initiator_id' => Auth::id(),
            'quantite_demandee' => $validated['quantite_demandee'],
            'statut' => 'en_attente_source',
        ]);

        $transfert->load(['source', 'destination', 'produit']);

        // Le vendeur de la boutique SOURCE doit autoriser la quantité à sortir.
        $this->notifyBoutiquiers(
            $transfert->source_boutique_id,
            'Transfert de stock à autoriser',
            "Le magasin demande le transfert de {$transfert->quantite_demandee} × {$transfert->produit->nom} vers {$transfert->destination->nom}. Autorisez la quantité à envoyer.",
            'Autoriser',
            route('boutiquier.transferts.index')
        );

        return redirect()->route('magasinier.transferts-stock.index')
            ->with('success', 'Transfert créé. Le point de vente source doit autoriser la quantité à envoyer.');
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
}
