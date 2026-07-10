<?php

namespace App\Http\Controllers\Boutiquier;

use App\Http\Controllers\Controller;
use App\Models\HoraireConnexion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));
        $user = Auth::user();
        $boutiqueId = $user->boutique_id;
        $boutique = \App\Models\Boutique::find($boutiqueId);

        if (!$boutique) {
            $produits = collect();
        } else {
            $produits = \App\Models\Produit::when($q, function ($query) use ($q) {
                $query->where('nom', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%");
            })
                ->with(['stocks' => function ($query) use ($boutiqueId) {
                    // Ordre FIFO (plus ancien lot d'abord) pour l'affichage par lot
                    $query->where('boutique_id', $boutiqueId)
                        ->orderBy('created_at')
                        ->orderBy('id');
                }])
                ->orderBy('nom')
                ->get();

            $visibleBoutiques = \App\Models\Boutique::where('id', '!=', $boutiqueId)
                ->orderBy('nom')
                ->get();

            $stockByBoutique = \App\Models\Stock::whereIn('boutique_id', $visibleBoutiques->pluck('id'))
                ->where('quantite', '>', 0)
                ->with('boutique')
                ->get()
                ->groupBy('produit_id');

            $produits->each(function ($produit) use ($stockByBoutique) {
                // Somme des lots par boutique (évite d'afficher plusieurs lignes / des 0)
                $produit->otherBoutiqueStocks = ($stockByBoutique->get($produit->id) ?? collect())
                    ->groupBy('boutique_id')
                    ->map(function ($lots) {
                        return (object) [
                            'boutique' => $lots->first()->boutique,
                            'quantite' => $lots->sum('quantite'),
                        ];
                    })
                    ->sortBy(fn($item) => $item->boutique?->nom ?? '')
                    ->values();
            });
        }

        $grossistes = \App\Models\Grossiste::with('prixProduits')->get();

        // Ventes du jour
        $ventesAujourdhui = \App\Models\Vente::where('boutique_id', $boutiqueId)
            ->whereDate('created_at', today())
            ->sum('montant_total');

        $nbVentesJour = \App\Models\Vente::where('boutique_id', $boutiqueId)
            ->whereDate('created_at', today())
            ->count();

        $dettes = \App\Models\Achat::with('paiements')
            ->where('statut', 'dette')
            ->where(function ($query) use ($boutiqueId) {
                // Dettes partagées (null) + dettes attribuées à cette boutique
                $query->whereNull('debit_boutique_id')
                    ->orWhere('debit_boutique_id', $boutiqueId);
            })
            ->get()
            ->filter(fn($achat) => $achat->reste_a_payer > 0);

        $dettesCount = $dettes->count();
        $dettesRestantes = $dettes->sum(fn($achat) => $achat->reste_a_payer);
        $notifications = $user->unreadNotifications;
        $shiftWarning = null;

        $remainingSeconds = HoraireConnexion::getRemainingSecondsForUser($user);
        if ($remainingSeconds !== null && $remainingSeconds > 0 && $remainingSeconds <= 1800) {
            $interval = HoraireConnexion::getCurrentIntervalForUser($user);
            $shiftWarning = [
                'minutes' => floor($remainingSeconds / 60),
                'seconds' => $remainingSeconds % 60,
                'end' => $interval->heure_fin,
            ];
        }

        return view('boutiquier.dashboard', compact('boutique', 'produits', 'grossistes', 'ventesAujourdhui', 'nbVentesJour', 'dettesCount', 'dettesRestantes', 'notifications', 'q', 'shiftWarning'));
    }

    public function markNotificationAsRead($notificationId)
    {
        $notification = Auth::user()->unreadNotifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
        }

        return back();
    }

    public function markAllNotificationsAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back();
    }
}
