<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Boutique;
use App\Models\Deduction;
use App\Models\User;
use App\Models\VenteLigne;

class SalaryPeriod extends Model
{
    protected $fillable = [
        'user_id',
        'period',
        'gross_salary',
        'primes',
        'carryover_previous',
        'deductions',
        'net_salary',
        'carryover_next',
        'status',
        'paid_amount',
        'paid_at',
        'paid_by',
        'payment_source_type',
        'payment_source_id',
    ];

    protected $casts = [
        'primes' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Sans cette valeur par défaut, une période fraîchement créée par
    // updateOrCreate n'a pas de statut en mémoire (il vient du défaut MySQL) :
    // estPaye() interrogerait alors une valeur nulle.
    protected $attributes = [
        'status' => self::STATUT_EN_ATTENTE,
    ];

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_PAYE = 'paye';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function estPaye(): bool
    {
        return $this->status === self::STATUT_PAYE;
    }

    /** Libellé de la source du règlement : « Solde personnel » ou le nom de la boutique. */
    public function getSourceLabelAttribute(): ?string
    {
        if (! $this->estPaye()) {
            return null;
        }

        if ($this->payment_source_type === 'admin') {
            return 'Solde personnel';
        }

        return Boutique::find($this->payment_source_id)?->nom ?? 'Point de vente supprimé';
    }

    public static function generateForPeriod(string $period)
    {
        $period = Carbon::createFromFormat('Y-m', $period)->format('Y-m');
        $employees = User::whereIn('role', ['magasinier', 'boutiquier', 'mecanicien'])->get();

        return $employees->map(function (User $user) use ($period) {
            return self::createOrUpdateForUserAndPeriod($user, $period);
        });
    }

    /**
     * Total des primes « hors heures » d'un employé sur un mois donné :
     * majorations encaissées sur les ventes qu'il a lui-même enregistrées.
     */
    public static function primesForUserAndPeriod(User $user, int $year, int $month): float
    {
        return (float) VenteLigne::query()
            ->join('ventes', 'ventes.id', '=', 'vente_lignes.vente_id')
            ->where('ventes.user_id', $user->id)
            ->whereNull('ventes.deleted_at')
            ->whereYear('ventes.created_at', $year)
            ->whereMonth('ventes.created_at', $month)
            ->sum('vente_lignes.prime_employe');
    }

    /**
     * Total des commissions figées d'un mécanicien sur un mois donné
     * (ventes client uniquement, ventes supprimées exclues).
     */
    public static function commissionsForUserAndPeriod(User $user, int $year, int $month): float
    {
        return (float) VenteLigne::query()
            ->join('ventes', 'ventes.id', '=', 'vente_lignes.vente_id')
            ->where('ventes.mecanicien_id', $user->id)
            ->whereNull('ventes.deleted_at')
            ->whereYear('ventes.created_at', $year)
            ->whereMonth('ventes.created_at', $month)
            ->sum('vente_lignes.commission_mecanicien');
    }

    public static function createOrUpdateForUserAndPeriod(User $user, string $period): self
    {
        // Un mois déjà réglé est FIGÉ : le recalcul tourne à chaque affichage de
        // la paie, et sans ce garde-fou une déduction validée ou une vente
        // enregistrée après le paiement réécrirait un montant déjà versé.
        $existant = self::where('user_id', $user->id)->where('period', $period)->first();
        if ($existant && $existant->estPaye()) {
            return $existant;
        }

        $date = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $year = $date->year;
        $month = $date->month;

        $previousCarryover = self::where('user_id', $user->id)
            ->where('period', '<', $period)
            ->orderByDesc('period')
            ->value('carryover_next') ?? 0;

        $approvedDeductions = Deduction::where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereYear('approved_at', $year)
            ->whereMonth('approved_at', $month)
            ->sum('amount');

        // Le mécanicien n'a pas de salaire de base : son brut correspond au
        // total des commissions gagnées sur les ventes client du mois.
        $baseSalary = $user->role === 'mecanicien'
            ? self::commissionsForUserAndPeriod($user, $year, $month)
            : $user->monthly_salary;

        // Primes « hors heures » : majorations encaissées sur ses propres ventes.
        $primes = self::primesForUserAndPeriod($user, $year, $month);

        $grossSalary = $baseSalary + $primes;

        $totalDeductions = $previousCarryover + $approvedDeductions;
        $netSalary = max(0, $grossSalary - $totalDeductions);
        $carryoverNext = max(0, $totalDeductions - $grossSalary);

        return self::updateOrCreate(
            ['user_id' => $user->id, 'period' => $period],
            [
                'gross_salary' => $grossSalary,
                'primes' => $primes,
                'carryover_previous' => $previousCarryover,
                'deductions' => $approvedDeductions,
                'net_salary' => $netSalary,
                'carryover_next' => $carryoverNext,
            ]
        );
    }
}
