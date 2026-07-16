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
                    <th class="p-4 font-semibold">Primes h. sup</th>
                    <th class="p-4 font-semibold">Salaire mensuel</th>
                    <th class="p-4 font-semibold">Déductions</th>
                    <th class="p-4 font-semibold">Report précédent</th>
                    <th class="p-4 font-semibold">Salaire à payer</th>
                    <th class="p-4 font-semibold">Report suivant</th>
                    <th class="p-4 font-semibold">Règlement</th>
                </tr>
            </thead>
            <tbody class="text-sm text-slate-700">
                @forelse($payrolls as $payroll)
                    <tr class="border-b border-slate-200 hover:bg-slate-50 transition-colors">
                        <td class="p-4 font-medium text-slate-800">{{ $payroll->user->nom_utilisateur }}</td>
                        <td class="p-4">{{ ucfirst($payroll->user->role) }}</td>
                        <td class="p-4">{{ number_format($payroll->gross_salary - $payroll->primes, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">
                            @if($payroll->primes > 0)
                                <span class="px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold" title="Majorations encaissées hors heures d'ouverture">
                                    + {{ number_format($payroll->primes, 0, ',', ' ') }} {{ param("currency") }}
                                </span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="p-4 font-semibold">{{ number_format($payroll->gross_salary, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">{{ number_format($payroll->deductions, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">{{ number_format($payroll->carryover_previous, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 font-semibold text-slate-900">{{ number_format($payroll->net_salary, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">{{ number_format($payroll->carryover_next, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">
                            @if($payroll->estPaye())
                                <div class="inline-flex flex-col">
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-xs font-bold">
                                        <i class="ri-checkbox-circle-line mr-1"></i>
                                        Payé — {{ number_format($payroll->paid_amount, 0, ',', ' ') }} {{ param("currency") }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 mt-1">
                                        Le {{ $payroll->paid_at->translatedFormat('d/m/Y à H:i') }} depuis <strong>{{ $payroll->source_label }}</strong>
                                    </span>
                                </div>
                            @elseif($payroll->net_salary <= 0)
                                <span class="text-slate-400 text-xs">Rien à payer</span>
                            @else
                                <div x-data="{ ouvert: false }" class="relative">
                                    <button type="button" @click="ouvert = !ouvert"
                                        class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-semibold whitespace-nowrap">
                                        <i class="ri-bank-card-line mr-1"></i> Valider et payer
                                    </button>

                                    <div x-show="ouvert" x-cloak @click.outside="ouvert = false"
                                        class="absolute right-0 z-20 mt-2 w-72 p-4 bg-white border border-slate-200 rounded-xl shadow-xl text-left">
                                        <p class="text-xs text-slate-500 mb-3">
                                            Régler <strong class="text-slate-800">{{ number_format($payroll->net_salary, 0, ',', ' ') }} {{ param("currency") }}</strong>
                                            à {{ $payroll->user->nom_utilisateur }}. Choisissez la source :
                                        </p>

                                        <form action="{{ route('admin.payroll.payer', $payroll) }}" method="POST"
                                            x-data="{ source: 'admin|' }"
                                            @submit="return confirm('Confirmer le paiement de ce salaire ? Le montant sera figé.')">
                                            @csrf
                                            <select x-model="source" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm mb-3">
                                                <option value="admin|">Mon solde personnel — {{ number_format($admin->solde_personnel, 0, ',', ' ') }} {{ param("currency") }}</option>
                                                @foreach($boutiques as $b)
                                                    <option value="boutique|{{ $b->id }}">{{ $b->nom }} — {{ number_format($b->solde, 0, ',', ' ') }} {{ param("currency") }}</option>
                                                @endforeach
                                            </select>

                                            <input type="hidden" name="source_type" :value="source.split('|')[0]">
                                            <input type="hidden" name="source_id" :value="source.split('|')[1]">

                                            <button type="submit" class="w-full px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm font-semibold">
                                                Confirmer le paiement
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-8 text-center text-slate-500">Aucun salarié trouvé pour cette période.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 text-sm text-slate-600">
    <p class="mb-2"><strong>Note :</strong> le salaire mensuel se réinitialise chaque mois. Les déductions enregistrées pour la période sont appliquées sur le salaire courant. Si le total des déductions dépasse le salaire, le montant restant est reporté sur le mois suivant.</p>
    <p><strong>Règlement :</strong> une fois payé, le montant est <strong>figé</strong> : les déductions validées ou les ventes enregistrées ensuite ne réécrivent plus un mois déjà réglé, elles jouent sur le mois suivant. Le paiement débite la source choisie — votre solde personnel ou la trésorerie d'un point de vente.</p>
</div>
@endsection
