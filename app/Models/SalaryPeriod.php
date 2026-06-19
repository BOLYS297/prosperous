<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Deduction;
use App\Models\User;

class SalaryPeriod extends Model
{
    protected $fillable = [
        'user_id',
        'period',
        'gross_salary',
        'carryover_previous',
        'deductions',
        'net_salary',
        'carryover_next',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateForPeriod(string $period)
    {
        $period = Carbon::createFromFormat('Y-m', $period)->format('Y-m');
        $employees = User::whereIn('role', ['magasinier', 'boutiquier'])->get();

        return $employees->map(function (User $user) use ($period) {
            return self::createOrUpdateForUserAndPeriod($user, $period);
        });
    }

    public static function createOrUpdateForUserAndPeriod(User $user, string $period): self
    {
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

        $grossSalary = $user->monthly_salary;
        $totalDeductions = $previousCarryover + $approvedDeductions;
        $netSalary = max(0, $grossSalary - $totalDeductions);
        $carryoverNext = max(0, $totalDeductions - $grossSalary);

        return self::updateOrCreate(
            ['user_id' => $user->id, 'period' => $period],
            [
                'gross_salary' => $grossSalary,
                'carryover_previous' => $previousCarryover,
                'deductions' => $approvedDeductions,
                'net_salary' => $netSalary,
                'carryover_next' => $carryoverNext,
            ]
        );
    }
}
