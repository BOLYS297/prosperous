@extends('layouts.admin')

@section('content')
<div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h1 class="text-3xl font-bold text-primary mb-2 tracking-tight">Rapport des bénéfices</h1>
        <p class="text-black">Bénéfice réel (prix de vente − coût d'achat) global et par point de vente.</p>
    </div>

    <form action="{{ route('admin.benefices.index') }}" method="GET" class="flex items-end gap-2 shrink-0">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Journée</label>
            <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-sm">
        </div>
        <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">Afficher</button>
        @if(! $date->isToday())
            <a href="{{ route('admin.benefices.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100 text-sm">Aujourd'hui</a>
        @endif
    </form>
</div>

@if($global['lignes_sans_cout'] > 0)
    <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-start">
        <i class="ri-alert-line text-lg mr-2 mt-0.5"></i>
        <div>
            <p class="font-semibold">{{ $global['lignes_sans_cout'] }} ligne(s) de vente sans coût d'achat enregistré</p>
            <p>Ces lignes proviennent de ventes antérieures à l'enregistrement automatique du coût : elles sont <strong>exclues du calcul</strong> (les compter donnerait une marge de 100 %, donc fausse). Les ventes réalisées à partir de maintenant sont toutes prises en compte.</p>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="glass-panel rounded-2xl p-6 border-b-4 border-emerald-500">
        <div class="text-sm text-slate-500 mb-1">Bénéfice du jour</div>
        <div class="text-3xl font-black {{ $global['benefice'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
            {{ number_format($global['benefice'], 0, ',', ' ') }} {{ param("currency") }}
        </div>
    </div>
    <div class="glass-panel rounded-2xl p-6">
        <div class="text-sm text-slate-500 mb-1">Chiffre d'affaires</div>
        <div class="text-2xl font-black text-slate-900">{{ number_format($global['ca_total'], 0, ',', ' ') }} {{ param("currency") }}</div>
    </div>
    <div class="glass-panel rounded-2xl p-6">
        <div class="text-sm text-slate-500 mb-1">Coût des marchandises</div>
        <div class="text-2xl font-black text-amber-600">{{ number_format($global['cout'], 0, ',', ' ') }} {{ param("currency") }}</div>
    </div>
    <div class="glass-panel rounded-2xl p-6">
        <div class="text-sm text-slate-500 mb-1">Marge</div>
        @php $marge = $global['ca_calculable'] > 0 ? ($global['benefice'] / $global['ca_calculable']) * 100 : 0; @endphp
        <div class="text-2xl font-black text-blue-600">{{ number_format($marge, 1, ',', ' ') }} %</div>
    </div>
</div>

@if($global['majoration_hors_heures'] > 0)
    <div class="mb-8 glass-panel rounded-2xl p-5 border-l-4 border-indigo-500">
        <div class="flex items-start gap-3">
            <i class="ri-moon-clear-line text-2xl text-indigo-600 mt-0.5"></i>
            <div class="flex-1">
                <div class="flex items-baseline justify-between gap-4">
                    <h3 class="font-bold text-slate-800">Dont majoration hors heures</h3>
                    <span class="text-xl font-black text-indigo-600">{{ number_format($global['majoration_hors_heures'], 0, ',', ' ') }} {{ param("currency") }}</span>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Ce montant est compris dans le bénéfice ci-dessus, mais il sera <strong>reversé aux employés</strong>
                    en prime d'heures supplémentaires. Bénéfice réellement conservé :
                    <strong class="text-slate-800">{{ number_format($global['benefice'] - $global['majoration_hors_heures'], 0, ',', ' ') }} {{ param("currency") }}</strong>.
                </p>
            </div>
        </div>
    </div>
@endif

{{-- Tendance 7 jours --}}
<div class="glass-panel rounded-2xl p-6 mb-8">
    <h2 class="text-lg font-bold text-slate-800 mb-4">Bénéfice des 7 derniers jours</h2>
    <div class="flex items-end justify-between gap-2 h-40">
        @foreach($tendance as $t)
            @php $h = $maxTendance > 0 ? max(2, ($t['benefice'] / $maxTendance) * 100) : 2; @endphp
            <div class="flex-1 flex flex-col items-center justify-end h-full">
                <div class="text-[10px] font-bold text-slate-600 mb-1">{{ number_format($t['benefice'], 0, ',', ' ') }}</div>
                <div class="w-full rounded-t-lg bg-gradient-to-t from-emerald-500 to-emerald-300" style="height: {{ $h }}%"></div>
                <div class="text-[10px] text-slate-500 mt-2 capitalize">{{ $t['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>

{{-- Détail par point de vente --}}
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-white/40">
        <h2 class="text-xl font-bold text-slate-800">Détail par point de vente — {{ $date->translatedFormat('l d F Y') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Point de vente</th>
                    <th class="p-4 font-semibold text-right">Chiffre d'affaires</th>
                    <th class="p-4 font-semibold text-right">Coût</th>
                    <th class="p-4 font-semibold text-right">Bénéfice</th>
                    <th class="p-4 font-semibold text-right">Marge</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($parBoutique as $row)
                    @php
                        $benefice = $row->ca_calculable - $row->cout;
                        $margeB = $row->ca_calculable > 0 ? ($benefice / $row->ca_calculable) * 100 : 0;
                    @endphp
                    <tr class="border-b border-white/20 hover:bg-white/30">
                        <td class="p-4 font-bold text-slate-800">
                            {{ $boutiques[$row->boutique_id]->nom ?? 'Boutique #' . $row->boutique_id }}
                            @if($row->lignes_sans_cout > 0)
                                <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-[10px] font-semibold" title="Lignes sans coût enregistré, exclues du bénéfice">
                                    {{ $row->lignes_sans_cout }} ligne(s) hors calcul
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-right text-slate-700">{{ number_format($row->ca_total, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 text-right text-amber-700">{{ number_format($row->cout, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 text-right font-black {{ $benefice < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($benefice, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 text-right font-semibold text-blue-600">{{ number_format($margeB, 1, ',', ' ') }} %</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <i class="ri-line-chart-line text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-800">Aucune vente ce jour</h3>
                            <p class="text-slate-500 mt-1">Choisissez une autre date pour consulter les bénéfices.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($parBoutique->isNotEmpty())
                <tfoot>
                    <tr class="bg-slate-50/70 font-bold">
                        <td class="p-4 text-slate-800">TOTAL</td>
                        <td class="p-4 text-right text-slate-800">{{ number_format($global['ca_total'], 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 text-right text-amber-700">{{ number_format($global['cout'], 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 text-right {{ $global['benefice'] < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($global['benefice'], 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4 text-right text-blue-600">{{ number_format($marge, 1, ',', ' ') }} %</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
