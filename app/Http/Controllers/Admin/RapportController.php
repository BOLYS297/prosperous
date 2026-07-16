<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vente;
use App\Models\Depense;
use App\Models\Perte;
use App\Models\Achat;
use App\Models\Boutique;
use App\Models\Stock;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        [$mois, $annee, $boutiqueId] = $this->filtres($request);

        $startDate = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        // Le filtre boutique s'applique à TOUT le rapport : sans cela, choisir un
        // point de vente ne changeait que le tableau des ventes journalières
        // pendant que les totaux continuaient d'agréger tout le monde.
        $pourBoutique = fn ($query, string $colonne = 'boutique_id') => $query
            ->when($boutiqueId, fn ($q) => $q->where($colonne, $boutiqueId));

        $totalVentes = $pourBoutique(Vente::whereBetween('created_at', [$startDate, $endDate]))->sum('montant_total');

        $totalDepenses = $pourBoutique(Depense::where('statut', 'approved')->whereBetween('created_at', [$startDate, $endDate]))->sum('montant');
        $totalDepensesPending = $pourBoutique(Depense::where('statut', 'pending')->whereBetween('created_at', [$startDate, $endDate]))->sum('montant');

        $totalPertes = $pourBoutique(Perte::where('statut', 'approved')->whereBetween('created_at', [$startDate, $endDate]))->count();
        $totalPertesPending = $pourBoutique(Perte::where('statut', 'pending')->whereBetween('created_at', [$startDate, $endDate]))->count();

        $totalAchats = $pourBoutique(Achat::whereBetween('created_at', [$startDate, $endDate]))->sum('montant_total');

        // Flux de trésorerie : encaissements moins décaissements. Ce n'est PAS le
        // bénéfice (les achats sont du stock, pas une charge consommée) : le
        // bénéfice réel, au coût FIFO figé, vit dans le rapport Bénéfices.
        $cashFlow = $totalVentes - ($totalDepenses + $totalAchats);

        $ventesParBoutique = Boutique::query()
            ->when($boutiqueId, fn ($q) => $q->where('id', $boutiqueId))
            ->withSum(['ventes' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }], 'montant_total')
            ->orderBy('nom')
            ->get();

        $ventesJournalieresParBoutique = Vente::select(
            'boutique_id',
            DB::raw('DATE(created_at) as jour'),
            DB::raw('SUM(montant_total) as total_ventes')
        )
            ->when($boutiqueId, fn ($q) => $q->where('boutique_id', $boutiqueId))
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
            ->when($boutiqueId, fn ($q) => $q->where('stocks.boutique_id', $boutiqueId))
            ->groupBy('boutique_id')
            ->get()
            ->keyBy('boutique_id');

        $ventesParBoutique->each(function ($boutique) use ($stockData) {
            $stock = $stockData->get($boutique->id);
            $boutique->stock_total_quantite = $stock ? (int) $stock->total_quantite : 0;
            $boutique->stock_total_capital = $stock ? (int) $stock->total_capital : 0;
            $boutique->stock_total_produits = $stock ? (int) $stock->total_produits : 0;
        });

        $boutiques = Boutique::orderBy('nom')->get();

        // Les 6 mois qui MÈNENT à la période choisie (et non « depuis
        // aujourd'hui ») : sinon, consulter mars 2024 affichait les six derniers
        // mois de l'année en cours.
        $statsMensuelles = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = $startDate->copy()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $statsMensuelles[] = [
                'mois' => ucfirst($date->translatedFormat('F Y')),
                'ventes' => $pourBoutique(Vente::whereBetween('created_at', [$monthStart, $monthEnd]))->sum('montant_total'),
                'depenses' => $pourBoutique(Depense::where('statut', 'approved')->whereBetween('created_at', [$monthStart, $monthEnd]))->sum('montant'),
            ];
        }

        $pendingDepenses = $pourBoutique(
            Depense::with(['boutique', 'user'])
                ->where('statut', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])
        )->orderByDesc('created_at')->limit(15)->get();

        $pendingPertes = $pourBoutique(
            Perte::with(['boutique', 'produit', 'user'])
                ->where('statut', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])
        )->orderByDesc('created_at')->limit(15)->get();

        // Stock réellement disponible, tous lots FIFO confondus : sert à signaler
        // une perte devenue impossible à valider.
        $stockDisponiblePertes = $pendingPertes->mapWithKeys(fn (Perte $p) => [
            $p->id => Stock::totalFor($p->boutique_id, $p->produit_id),
        ]);

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
            'stockDisponiblePertes',
            'mois',
            'annee',
            'boutiques',
            'boutiqueId'
        ));
    }

    /**
     * Mois / année / boutique demandés, ramenés à des valeurs sûres.
     * Sans ce filtrage, ?mois=abc faisait planter Carbon en erreur 500.
     */
    protected function filtres(Request $request): array
    {
        $mois = (int) $request->input('mois', date('m'));
        if ($mois < 1 || $mois > 12) {
            $mois = (int) date('m');
        }

        $annee = (int) $request->input('annee', date('Y'));
        if ($annee < 2000 || $annee > (int) date('Y') + 1) {
            $annee = (int) date('Y');
        }

        $boutiqueId = $request->input('boutique_id');
        $boutiqueId = $boutiqueId && Boutique::whereKey($boutiqueId)->exists() ? (int) $boutiqueId : null;

        return [sprintf('%02d', $mois), $annee, $boutiqueId];
    }

    public function exportCsv(Request $request)
    {
        [$mois, $annee, $boutiqueId] = $this->filtres($request);

        $startDate = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($annee, $mois, 1)->endOfMonth();

        $ventes = Vente::with(['boutique', 'user'])
            ->when($boutiqueId, fn ($q) => $q->where('boutique_id', $boutiqueId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        $suffixe = $boutiqueId ? '_boutique_' . $boutiqueId : '';
        $filename = "rapport_ventes_{$annee}_{$mois}{$suffixe}.csv";

        // BOM UTF-8 pour qu'Excel ouvre le fichier avec les bons accents.
        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($ventes) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");

            // La table `ventes` n'a pas de colonne `statut` : l'ancienne colonne
            // « Statut » affichait « Payée » en dur sur chaque ligne. On exporte
            // à la place des informations réellement présentes.
            fputcsv($file, [
                'Date',
                'Boutique',
                'Vendeur',
                'Type',
                'Tarif',
                'Montant (' . param('currency') . ')',
            ], ';');

            foreach ($ventes as $vente) {
                fputcsv($file, [
                    $vente->created_at ? $vente->created_at->format('d/m/Y H:i:s') : '—',
                    $vente->boutique->nom ?? 'Inconnue',
                    $vente->user->nom_utilisateur ?? '—',
                    $vente->grossiste_id ? 'Grossiste' : 'Client',
                    $vente->hors_heures ? 'Majoré' : 'Normal',
                    $vente->montant_total,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Validation / rejet en direct.
     *
     * Ces actions étaient mises en file (ShouldQueue) alors qu'aucun worker ne
     * tournait : elles annonçaient « en cours de validation » et n'arrivaient
     * jamais. Ce sont de simples écritures : on les exécute maintenant sur-le-champ,
     * et le message dit ce qui s'est réellement passé.
     */
    public function approveDepense(Depense $depense)
    {
        if ($depense->statut !== 'pending') {
            return back()->with('error', 'Cette dépense ne peut pas être validée.');
        }

        \App\Jobs\ApproveDepense::dispatchSync($depense, Auth::id());

        return back()->with('success', 'Dépense validée. Le solde du point de vente a été débité.');
    }

    public function rejectDepense(Depense $depense)
    {
        if ($depense->statut !== 'pending') {
            return back()->with('error', 'Cette dépense ne peut pas être rejetée.');
        }

        \App\Jobs\RejectDepense::dispatchSync($depense, Auth::id());

        return back()->with('success', 'Dépense rejetée.');
    }

    public function approvePerte(Perte $perte)
    {
        if ($perte->statut !== 'pending') {
            return back()->with('error', 'Cette perte ne peut pas être validée.');
        }

        // Stock FIFO : le disponible est la somme de TOUS les lots. L'ancien
        // contrôle ne lisait qu'un seul lot et refusait donc à tort des pertes
        // valides dès qu'un produit avait plusieurs lots.
        $disponible = Stock::totalFor($perte->boutique_id, $perte->produit_id);

        if ($disponible < $perte->quantite) {
            return back()->with('error', "Stock insuffisant pour valider cette perte : {$disponible} en stock, {$perte->quantite} demandé(s).");
        }

        \App\Jobs\ApprovePerte::dispatchSync($perte, Auth::id());

        return back()->with('success', 'Perte validée. Le stock a été sorti.');
    }

    public function rejectPerte(Perte $perte)
    {
        if ($perte->statut !== 'pending') {
            return back()->with('error', 'Cette perte ne peut pas être rejetée.');
        }

        \App\Jobs\RejectPerte::dispatchSync($perte, Auth::id());

        return back()->with('success', 'Perte rejetée. Le stock reste inchangé.');
    }
}
