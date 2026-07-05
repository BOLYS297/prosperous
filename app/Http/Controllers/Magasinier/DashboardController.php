<?php

namespace App\Http\Controllers\Magasinier;

use App\Http\Controllers\Controller;
use App\Models\HoraireConnexion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $boutique = $user->boutique;

        if (! $boutique) {
            // L'utilisateur magasinier n'a pas de boutique assignée
            return redirect()->route('dashboard')->withErrors(['boutique' => 'Aucune boutique assignée à votre compte.']);
        }

        $totalProduits = \App\Models\Stock::where('boutique_id', $boutique->id)
            ->where('quantite', '>', 0)
            ->distinct('produit_id')
            ->count('produit_id');

        // Compte des produits dont la somme des quantités pour la boutique est <= 0
        $ruptures = DB::table('produits')
            ->leftJoin('stocks', function ($join) use ($boutique) {
                $join->on('produits.id', '=', 'stocks.produit_id')
                    ->where('stocks.boutique_id', $boutique->id);
            })
            ->select(DB::raw('produits.id, COALESCE(SUM(stocks.quantite),0) as total'))
            ->groupBy('produits.id')
            ->havingRaw('COALESCE(SUM(stocks.quantite),0) <= 0')
            ->get()
            ->count();
        $pertesMois = \App\Models\Perte::where('boutique_id', $boutique->id)
            ->where('statut', 'approved')
            ->whereMonth('created_at', now()->month)
            ->count();

        $recharges = \App\Models\Recharge::with(['fournisseur', 'lignes.produit'])
            ->where('destination_id', $boutique->id)
            ->where('statut', 'en_attente')
            ->orderBy('created_at', 'desc')
            ->get();

        $rechargeCount = $recharges->count();

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

        return view('magasinier.dashboard', compact('boutique', 'totalProduits', 'ruptures', 'pertesMois', 'rechargeCount', 'recharges', 'shiftWarning'));
    }
}
