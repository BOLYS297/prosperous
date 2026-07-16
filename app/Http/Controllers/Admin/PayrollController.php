<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSoldeMouvement;
use App\Models\Boutique;
use App\Models\LogActivite;
use App\Models\SalaryPeriod;
use App\Models\User;
use App\Notifications\PendingActionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', Carbon::now()->format('Y-m'));
        $period = Carbon::createFromFormat('Y-m', $period)->format('Y-m');

        SalaryPeriod::generateForPeriod($period);

        $payrolls = SalaryPeriod::with('user', 'paidBy')
            ->where('period', $period)
            ->orderBy('user_id')
            ->get();

        $periods = SalaryPeriod::select('period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period');

        // Sources de règlement possibles : le solde personnel de l'admin et la
        // trésorerie de chaque point de vente.
        $admin = Auth::user();
        $boutiques = Boutique::orderBy('type')->orderBy('nom')->get();

        return view('admin.payroll.index', compact('payrolls', 'period', 'periods', 'admin', 'boutiques'));
    }

    /**
     * Valide et règle le salaire d'un employé pour une période.
     *
     * Le montant est débité de la source choisie (solde personnel de l'admin ou
     * trésorerie d'un point de vente) puis FIGÉ sur la période, qui ne sera plus
     * recalculée. Tout se joue dans une transaction avec verrous : un double
     * clic ne peut pas payer deux fois.
     */
    public function payer(Request $request, SalaryPeriod $payroll)
    {
        $validated = $request->validate([
            'source_type' => 'required|in:admin,boutique',
            'source_id' => 'required_if:source_type,boutique|nullable|exists:boutiques,id',
        ]);

        $admin = Auth::user();
        $estAdmin = $validated['source_type'] === 'admin';

        $dejaPaye = false;
        $rienAPayer = false;
        $soldeInsuffisant = false;
        $montantPaye = 0;
        $sourceNom = '';

        DB::transaction(function () use ($payroll, $admin, $estAdmin, $validated, &$dejaPaye, &$rienAPayer, &$soldeInsuffisant, &$montantPaye, &$sourceNom) {
            $fresh = SalaryPeriod::where('id', $payroll->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->estPaye()) {
                $dejaPaye = true;
                return;
            }

            $montant = round((float) $fresh->net_salary, 2);
            if ($montant <= 0) {
                $rienAPayer = true;
                return;
            }

            if ($estAdmin) {
                $source = User::where('id', $admin->id)->lockForUpdate()->first();
                if ($montant > (float) $source->solde_personnel) {
                    $soldeInsuffisant = true;
                    return;
                }

                AdminSoldeMouvement::enregistrer(
                    $admin->id,
                    'salaire',
                    -$montant,
                    'Salaire ' . $fresh->period . ' — ' . $fresh->user->nom_utilisateur,
                    null,
                    'salary_period',
                    $fresh->id
                );

                $sourceNom = 'votre solde personnel';
            } else {
                $source = Boutique::where('id', $validated['source_id'])->lockForUpdate()->first();
                if (! $source) {
                    $rienAPayer = true;
                    return;
                }
                if ($montant > (float) $source->solde) {
                    $soldeInsuffisant = true;
                    return;
                }

                $source->decrement('solde', $montant);
                $sourceNom = $source->nom;
            }

            $fresh->update([
                'status' => SalaryPeriod::STATUT_PAYE,
                'paid_amount' => $montant,
                'paid_at' => now(),
                'paid_by' => $admin->id,
                'payment_source_type' => $estAdmin ? 'admin' : 'boutique',
                'payment_source_id' => $estAdmin ? $admin->id : $source->id,
            ]);

            $montantPaye = $montant;
        });

        if ($dejaPaye) {
            return back()->with('error', 'Ce salaire a déjà été payé.');
        }
        if ($rienAPayer) {
            return back()->with('error', 'Aucun montant à payer pour cette période.');
        }
        if ($soldeInsuffisant) {
            return back()->with('error', 'Solde insuffisant sur la source choisie pour régler ce salaire.');
        }

        $payroll->refresh();
        $this->notifierEmploye($payroll, $montantPaye);

        LogActivite::create([
            'user_id' => $admin->id,
            'action' => 'admin.payroll.payer',
            'description' => 'Salaire ' . $payroll->period . ' de ' . $payroll->user->nom_utilisateur
                . ' payé (' . money_format_app($montantPaye) . ") depuis {$sourceNom}.",
        ]);

        return back()->with('success', 'Salaire de ' . $payroll->user->nom_utilisateur . ' payé : '
            . money_format_app($montantPaye) . " depuis {$sourceNom}.");
    }

    protected function notifierEmploye(SalaryPeriod $payroll, float $montant): void
    {
        $payroll->user->notify(new PendingActionNotification(
            'Salaire payé',
            'Votre salaire de ' . $payroll->period . ' vous a été versé : ' . money_format_app($montant) . '.',
            'Voir',
            url('/'),
            ['type' => 'salaire_paye', 'montant' => $montant]
        ));
    }
}
