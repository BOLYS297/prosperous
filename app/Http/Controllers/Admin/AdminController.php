<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achat;
use App\Models\Boutique;
use App\Models\DeductionSetting;
use App\Models\Deduction;
use App\Models\Depense;
use App\Models\LogActivite;
use App\Notifications\SalaryDeductionNotification;
use App\Models\Recharge;
use App\Models\Vente;
use App\Models\VenteLigne;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $deductionSearch = trim($request->query('deduction_search', ''));

        $entrepriseSolde = Boutique::sum('solde');
        $boutiques = Boutique::orderByDesc('solde')->take(2)->get();
        $topBoutique = $boutiques->get(0);
        $secondBoutique = $boutiques->get(1);

        $dettesFournisseurs = Achat::where('statut', 'dette')
            ->with('paiements')
            ->get()
            ->sum(fn(Achat $achat) => $achat->reste_a_payer);

        $salesRangeStart = Carbon::today()->subDays(6)->startOfDay();
        $salesRangeEnd = Carbon::today()->endOfDay();

        $weeklySalesRaw = Vente::selectRaw('DATE(created_at) as date, SUM(montant_total) as total')
            ->whereBetween('created_at', [$salesRangeStart, $salesRangeEnd])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $weeklySales = collect();
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $day = Carbon::today()->subDays($daysAgo);
            $dayKey = $day->format('Y-m-d');
            $salesRow = $weeklySalesRaw->get($dayKey);

            $weeklySales->push([
                'label' => $day->translatedFormat('D'),
                'date' => $dayKey,
                'total' => $salesRow ? (float) $salesRow->total : 0,
            ]);
        }

        $maxWeeklySales = $weeklySales->max('total') ?: 1;

        $recentActivities = LogActivite::latest()->take(5)->get();

        $totalVentes = Vente::sum('montant_total');
        $totalDepenses = Depense::where('statut', 'approved')->sum('montant');
        $totalAchats = Achat::sum('montant_total');
        $profitNet = $totalVentes - ($totalDepenses + $totalAchats);

        $rechargesEnAttente = Recharge::where('statut', 'confirmee_par_magasinier')->count();
        $rechargesAnomalies = Recharge::where('statut', 'anomalie')->count();
        $deductionHourlyAmount = DeductionSetting::getHourlyAmount();
        $topProductsCount = DeductionSetting::getTopProductsCount();
        $pendingDeductions = Deduction::with('user')
            ->where('status', 'pending')
            ->when($deductionSearch, function ($query) use ($deductionSearch) {
                $query->where(function ($query) use ($deductionSearch) {
                    $query->where('amount', 'like', "%{$deductionSearch}%")
                        ->orWhere('event_type', 'like', "%{$deductionSearch}%")
                        ->orWhereHas('user', function ($query) use ($deductionSearch) {
                            $query->where('nom_utilisateur', 'like', "%{$deductionSearch}%");
                        });
                });
            })
            ->orderByDesc('actual_login_at')
            ->get();

        $approvedDeductions = Deduction::with('user', 'approver')
            ->where('status', 'approved')
            ->when($deductionSearch, function ($query) use ($deductionSearch) {
                $query->where(function ($query) use ($deductionSearch) {
                    $query->where('amount', 'like', "%{$deductionSearch}%")
                        ->orWhere('event_type', 'like', "%{$deductionSearch}%")
                        ->orWhereHas('user', function ($query) use ($deductionSearch) {
                            $query->where('nom_utilisateur', 'like', "%{$deductionSearch}%");
                        });
                });
            })
            ->orderByDesc('approved_at')
            ->take(50)
            ->get();

        $topProducts = VenteLigne::select(
            'produits.nom as produit_nom',
            'produits.reference as produit_reference',
            DB::raw('SUM(vente_lignes.quantite) as total_quantity'),
            DB::raw('SUM(vente_lignes.quantite * vente_lignes.prix_unitaire) as total_revenue')
        )
            ->join('produits', 'produits.id', '=', 'vente_lignes.produit_id')
            ->groupBy('vente_lignes.produit_id', 'produits.nom', 'produits.reference')
            ->orderByDesc('total_quantity')
            ->take($topProductsCount)
            ->get();

        return view('admin.dashboard', compact(
            'entrepriseSolde',
            'topBoutique',
            'secondBoutique',
            'dettesFournisseurs',
            'weeklySales',
            'maxWeeklySales',
            'recentActivities',
            'rechargesEnAttente',
            'rechargesAnomalies',
            'deductionHourlyAmount',
            'topProductsCount',
            'pendingDeductions',
            'approvedDeductions',
            'topProducts',
            'deductionSearch',
            'profitNet'
        ));
    }

    public function updateDeductionAmount(Request $request)
    {
        $request->validate([
            'hourly_retard_amount' => ['sometimes', 'required', 'integer', 'min:0'],
            'top_products_count' => ['sometimes', 'required', 'integer', 'min:1', 'max:20'],
        ]);

        $setting = DeductionSetting::current();

        if ($request->has('hourly_retard_amount')) {
            $setting->hourly_retard_amount = $request->input('hourly_retard_amount');
        }

        if ($request->has('top_products_count')) {
            $setting->top_products_count = $request->input('top_products_count');
        }

        $setting->save();

        return back()->with('status', 'Paramètres du dashboard enregistrés avec succès.');
    }

    public function approveDeduction(Request $request, Deduction $deduction)
    {
        if ($deduction->status !== 'pending') {
            return back()->with('error', 'Cette déduction a déjà été traitée.');
        }

        $deduction->status = 'approved';
        $deduction->approved_by = auth()->id();
        $deduction->approved_at = now();
        $deduction->save();

        if ($deduction->user) {
            $deduction->user->notify(new SalaryDeductionNotification(
                'Déduction salariale approuvée',
                "Votre déduction salariale de {$deduction->amount} FCFA a été approuvée par l'administrateur.",
                'Voir les détails',
                route('dashboard')
            ));
        }

        LogActivite::create([
            'user_id' => auth()->id(),
            'action' => 'admin.deductions.approve',
            'description' => "Déduction approuvée pour l'utilisateur {$deduction->user->nom_utilisateur} : {$deduction->amount} FCFA",
        ]);

        return back()->with('status', 'Déduction approuvée et enregistrée.');
    }


    public function rejectDeduction(Request $request, Deduction $deduction)
    {
        // On autorise le rejet d'une déduction en attente OU déjà approuvée.
        // Une déduction déjà rejetée ne peut pas l'être une seconde fois.
        if ($deduction->status === 'rejected') {
            return back()->with('error', 'Cette déduction a déjà été rejetée.');
        }

        $wasApproved = $deduction->status === 'approved';

        $deduction->status = 'rejected';
        $deduction->approved_by = auth()->id();
        $deduction->approved_at = now();
        $deduction->save();

        // Rejeter une déduction approuvée la retire automatiquement du calcul du
        // salaire : SalaryPeriod::generateForPeriod ne somme que les déductions
        // dont le statut est 'approved' (recalcul au prochain affichage de la paie).

        if ($deduction->user) {
            $message = $wasApproved
                ? "Votre déduction salariale de {$deduction->amount} FCFA, précédemment validée, a finalement été annulée par l'administrateur."
                : "Votre déduction salariale de {$deduction->amount} FCFA a été rejetée par l'administrateur.";

            $deduction->user->notify(new SalaryDeductionNotification(
                $wasApproved ? 'Déduction salariale annulée' : 'Déduction salariale rejetée',
                $message,
                'Voir les détails',
                route('dashboard')
            ));
        }

        LogActivite::create([
            'user_id' => auth()->id(),
            'action' => 'admin.deductions.reject',
            'description' => $wasApproved
                ? "Déduction validée annulée pour l'utilisateur {$deduction->user->nom_utilisateur} : {$deduction->amount} FCFA."
                : "Déduction rejetée pour l'utilisateur {$deduction->user->nom_utilisateur}.",
        ]);

        return back()->with('status', $wasApproved
            ? 'Déduction validée annulée. Le salaire sera recalculé automatiquement.'
            : 'Demande de déduction rejetée.');
    }
}
