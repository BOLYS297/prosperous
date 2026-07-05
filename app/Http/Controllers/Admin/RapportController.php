<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vente;
use App\Models\Depense;
use App\Models\Perte;
use App\Models\Achat;
use App\Models\Boutique;
use App\Models\Stock;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminValidationNotification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        $mois = $request->input('mois', date('m'));
        $annee = $request->input('annee', date('Y'));
        $boutiqueId = $request->input('boutique_id');

        $startDate = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        // 1. Total Ventes (Sur la période)
        $totalVentes = Vente::whereBetween('created_at', [$startDate, $endDate])->sum('montant_total');

        // 2. Dépenses validées
        $totalDepenses = Depense::where('statut', 'approved')->whereBetween('created_at', [$startDate, $endDate])->sum('montant');
        $totalDepensesPending = Depense::where('statut', 'pending')->whereBetween('created_at', [$startDate, $endDate])->sum('montant');

        // 3. Pertes validées
        $totalPertes = Perte::where('statut', 'approved')->whereBetween('created_at', [$startDate, $endDate])->count();
        $totalPertesPending = Perte::where('statut', 'pending')->whereBetween('created_at', [$startDate, $endDate])->count();

        // 4. Total Achats (Stock entrant)
        $totalAchats = Achat::whereBetween('created_at', [$startDate, $endDate])->sum('montant_total');

        // Flux de trésorerie (Bénéfice estimé global)
        $cashFlow = $totalVentes - ($totalDepenses + $totalAchats);

        // Ventes par boutique et totaux de stock
        $ventesParBoutique = Boutique::withSum(['ventes' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }], 'montant_total')
            ->withSum('stocks', 'quantite')
            ->get();

        $ventesJournalieresParBoutique = Vente::select(
            'boutique_id',
            DB::raw('DATE(created_at) as jour'),
            DB::raw('SUM(montant_total) as total_ventes')
        )
            ->when($boutiqueId, function ($query) use ($boutiqueId) {
                $query->where('boutique_id', $boutiqueId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('boutique_id', DB::raw('DATE(created_at)'))
            ->orderBy('jour')
            ->orderBy('boutique_id')
            ->get()
            ->groupBy('jour');

        $stockData = Stock::select(
            'boutique_id',
            DB::raw('SUM(stocks.quantite) as total_quantite'),
            DB::raw('SUM(stocks.quantite * produits.prix_achat) as total_capital'),
            DB::raw('COUNT(DISTINCT stocks.produit_id) as total_produits')
        )
            ->leftJoin('produits', 'stocks.produit_id', '=', 'produits.id')
            ->groupBy('boutique_id')
            ->get()
            ->keyBy('boutique_id');

        $ventesParBoutique->each(function ($boutique) use ($stockData) {
            $stock = $stockData->get($boutique->id);
            $boutique->stock_total_quantite = $stock ? (int)$stock->total_quantite : 0;
            $boutique->stock_total_capital = $stock ? (int)$stock->total_capital : 0;
            $boutique->stock_total_produits = $stock ? (int)$stock->total_produits : 0;
        });

        $boutiques = Boutique::orderBy('nom')->get();

        // Statistiques mensuelles pour le graphique (6 derniers mois)
        $statsMensuelles = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $v = Vente::whereBetween('created_at', [$monthStart, $monthEnd])->sum('montant_total');
            $d = Depense::where('statut', 'approved')->whereBetween('created_at', [$monthStart, $monthEnd])->sum('montant');

            $statsMensuelles[] = [
                'mois' => ucfirst($date->translatedFormat('F Y')),
                'ventes' => $v,
                'depenses' => $d
            ];
        }

        $pendingDepenses = Depense::with(['boutique', 'user'])
            ->where('statut', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $pendingPertes = Perte::with(['boutique', 'produit', 'user'])
            ->where('statut', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('admin.rapports.index', compact(
            'totalVentes',
            'totalDepenses',
            'totalDepensesPending',
            'totalPertes',
            'totalPertesPending',
            'totalAchats',
            'cashFlow',
            'ventesParBoutique',
            'ventesJournalieresParBoutique',
            'statsMensuelles',
            'pendingDepenses',
            'pendingPertes',
            'mois',
            'annee',
            'boutiques',
            'boutiqueId'
        ));
    }

    public function exportCsv(Request $request)
    {
        $mois = $request->input('mois', date('m'));
        $annee = $request->input('annee', date('Y'));
        $startDate = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        $ventes = Vente::with('boutique')->whereBetween('created_at', [$startDate, $endDate])->get();

        $filename = "rapport_ventes_{$annee}_{$mois}.csv";

        // Ajout du BOM pour qu'Excel ouvre le CSV correctement en UTF-8
        $headers = [
            "Content-type"        => "application/vnd.ms-excel; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($ventes) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($file, ['Date d\'enregistrement', 'Boutique', 'Montant (FCFA)', 'Statut'], ';');

            foreach ($ventes as $vente) {
                $date = $vente->created_at ? $vente->created_at->format('d/m/Y H:i:s') : 'N/A';

                fputcsv($file, [
                    $date,
                    $vente->boutique->nom ?? 'Inconnue',
                    $vente->montant_total,
                    $vente->statut ?? 'Payée'
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function approveDepense(Depense $depense)
    {
        if ($depense->statut !== 'pending') {
            return back()->with('error', 'Cette dépense ne peut pas être validée.');
        }

        // Dispatcher le job en background (asynchrone via queue)
        \App\Jobs\ApproveDepense::dispatch($depense, Auth::id());

        return back()->with('success', 'Dépense en cours de validation. Vous serez notifié une fois traitée.');
    }

    public function rejectDepense(Depense $depense)
    {
        if ($depense->statut !== 'pending') {
            return back()->with('error', 'Cette dépense ne peut pas être rejetée.');
        }

        // Dispatcher le job en background (asynchrone via queue)
        \App\Jobs\RejectDepense::dispatch($depense, Auth::id());

        return back()->with('success', 'Dépense en cours de rejet. Vous serez notifié une fois traitée.');
    }

    public function approvePerte(Perte $perte)
    {
        if ($perte->statut !== 'pending') {
            return back()->with('error', 'Cette perte ne peut pas être validée.');
        }

        $stock = Stock::where('boutique_id', $perte->boutique_id)
            ->where('produit_id', $perte->produit_id)
            ->first();

        if (!$stock || $stock->quantite < $perte->quantite) {
            return back()->with('error', 'Stock insuffisant pour valider cette perte.');
        }

        // Dispatcher le job en background (asynchrone via queue)
        \App\Jobs\ApprovePerte::dispatch($perte, Auth::id());

        return back()->with('success', 'Perte en cours de validation. Vous serez notifié une fois traitée.');
    }

    public function rejectPerte(Perte $perte)
    {
        if ($perte->statut !== 'pending') {
            return back()->with('error', 'Cette perte ne peut pas être rejetée.');
        }

        // Dispatcher le job en background (asynchrone via queue)
        \App\Jobs\RejectPerte::dispatch($perte, Auth::id());

        return back()->with('success', 'Perte en cours de rejet. Vous serez notifié une fois traitée.');
    }
}
