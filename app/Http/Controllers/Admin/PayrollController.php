<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', Carbon::now()->format('Y-m'));
        $period = Carbon::createFromFormat('Y-m', $period)->format('Y-m');

        SalaryPeriod::generateForPeriod($period);

        $payrolls = SalaryPeriod::with('user')
            ->where('period', $period)
            ->orderBy('user_id')
            ->get();

        $periods = SalaryPeriod::select('period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period');

        return view('admin.payroll.index', compact('payrolls', 'period', 'periods'));
    }
}
