@extends('layouts.admin')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Paie des employés</h2>
        <p class="text-black">Consultez les salaires mensuels, les déductions et les reports vers le mois suivant.</p>
    </div>
    <div class="flex items-center gap-3">
        <span class="text-sm text-slate-500">Période :</span>
        <select onchange="location = this.value" class="px-4 py-3 border border-slate-300 rounded-2xl bg-white text-slate-900">
            @foreach($periods as $availablePeriod)
                <option value="{{ route('admin.payroll.index', ['period' => $availablePeriod]) }}" {{ $period === $availablePeriod ? 'selected' : '' }}>{{ \Carbon\Carbon::createFromFormat('Y-m', $availablePeriod)->translatedFormat('F Y') }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-6 p-6 rounded-3xl bg-slate-50 border border-slate-200">
    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
        <div class="flex-1">
            <h3 class="text-xl font-semibold text-slate-800">Report et réinitialisation mensuelle</h3>
            <p class="text-sm text-slate-600">Chaque mois, le salaire est recalculé. Si les déductions dépassent le salaire, le solde est reporté sur le mois suivant.</p>
        </div>
        <div class="text-sm text-slate-700">
            <strong>Période affichée :</strong> {{ \Carbon\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}
        </div>
    </div>
</div>

<div class="glass-panel rounded-3xl overflow-hidden border border-slate-200">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-200 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Employé</th>
                    <th class="p-4 font-semibold">Rôle</th>
                    <th class="p-4 font-semibold">Salaire normal</th>
                    <th class="p-4 font-semibold">Salaire mensuel</th>
                    <th class="p-4 font-semibold">Déductions</th>
                    <th class="p-4 font-semibold">Report précédent</th>
                    <th class="p-4 font-semibold">Salaire à payer</th>
                    <th class="p-4 font-semibold">Report suivant</th>
                </tr>
            </thead>
            <tbody class="text-sm text-slate-700">
                @forelse($payrolls as $payroll)
                    <tr class="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                        <td class="p-4 font-medium text-slate-800">{{ $payroll->user->nom_utilisateur }}</td>
                        <td class="p-4">{{ ucfirst($payroll->user->role) }}</td>
                        <td class="p-4">{{ number_format($payroll->gross_salary, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">{{ number_format($payroll->gross_salary, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">{{ number_format($payroll->deductions, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">{{ number_format($payroll->carryover_previous, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 font-semibold text-slate-900">{{ number_format($payroll->net_salary, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">{{ number_format($payroll->carryover_next, 0, ',', ' ') }} {{ param("currency") }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-slate-500">Aucun salarié trouvé pour cette période.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 text-sm text-slate-600">
    <p class="mb-2"><strong>Note :</strong> le salaire mensuel se réinitialise chaque mois. Les déductions enregistrées pour la période sont appliquées sur le salaire courant. Si le total des déductions dépasse le salaire, le montant restant est reporté sur le mois suivant.</p>
</div>
@endsection
