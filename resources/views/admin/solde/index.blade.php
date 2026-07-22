@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-primary mb-2 tracking-tight">Mon solde personnel</h1>
    <p class="text-black">Récupérez les recettes des points de vente, suivez vos mouvements et réglez vos achats/dépenses.</p>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
        <i class="ri-checkbox-circle-line text-lg mr-2"></i><span>{{ session('success') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center">
        <i class="ri-error-warning-line text-lg mr-2"></i><span>{{ session('error') }}</span>
    </div>
@endif
@if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
        <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="glass-panel rounded-2xl p-6 border-b-4 border-emerald-500">
        <div class="text-sm text-slate-500 mb-1">Solde personnel</div>
        <div class="text-3xl font-black {{ $admin->solde_personnel < 0 ? 'text-rose-600' : 'text-slate-900' }}">
            {{ number_format($admin->solde_personnel, 0, ',', ' ') }} {{ param("currency") }}
        </div>
    </div>
    <div class="glass-panel rounded-2xl p-6">
        <div class="text-sm text-slate-500 mb-1">Total encaissé (recettes)</div>
        <div class="text-2xl font-black text-emerald-600">{{ number_format($totaux['recettes'], 0, ',', ' ') }} {{ param("currency") }}</div>
    </div>
    <div class="glass-panel rounded-2xl p-6">
        <div class="text-sm text-slate-500 mb-1">Total sorties</div>
        <div class="text-2xl font-black text-rose-600">{{ number_format($totaux['sorties'], 0, ',', ' ') }} {{ param("currency") }}</div>
    </div>
</div>

{{-- Récupération des recettes --}}
<div class="glass-panel rounded-2xl p-6 mb-8" x-data="{ tout(id, solde) { document.getElementById('montant_' + id).value = solde; document.getElementById('check_' + id).checked = solde > 0; } }">
    <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center">
        <i class="ri-hand-coin-line text-2xl text-emerald-600 mr-2"></i> Récupérer les recettes
    </h2>
    <p class="text-sm text-slate-500 mb-6">Cochez les points de vente et saisissez le montant à récupérer. Le boutiquier devra <strong>valider</strong> avant que son solde ne soit débité et que le vôtre ne soit crédité.</p>

    <form action="{{ route('admin.solde.recuperer') }}" method="POST">
        @csrf
        <div class="space-y-3 mb-6">
            @foreach($boutiques as $i => $b)
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl border border-slate-200 bg-white/60">
                    <label class="flex items-center gap-3 flex-1 cursor-pointer">
                        <input type="checkbox" id="check_{{ $b->id }}" class="rounded border-slate-300 text-emerald-600" onchange="document.getElementById('bid_{{ $b->id }}').disabled = !this.checked; document.getElementById('montant_{{ $b->id }}').disabled = !this.checked;">
                        <span>
                            <span class="font-semibold text-slate-800">{{ $b->nom }}</span>
                            <span class="text-xs text-slate-400 ml-1 capitalize">({{ $b->type }})</span>
                            <span class="block text-xs text-slate-500">Trésorerie : <strong>{{ number_format($b->solde, 0, ',', ' ') }} {{ param("currency") }}</strong></span>
                        </span>
                    </label>

                    <input type="hidden" id="bid_{{ $b->id }}" name="recettes[{{ $i }}][boutique_id]" value="{{ $b->id }}" disabled>
                    <div class="flex items-end gap-2">
                        <input type="number" id="montant_{{ $b->id }}" name="recettes[{{ $i }}][montant]" min="1" max="{{ (int) $b->solde }}" step="1" placeholder="Montant" disabled
                            class="w-36 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:bg-slate-100">
                        <button type="button" @click="tout({{ $b->id }}, {{ (int) $b->solde }})" class="px-3 py-2 text-xs font-semibold bg-slate-800 text-white rounded-lg hover:bg-slate-900">Tout</button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Motif</label>
                <input type="text" name="motif" maxlength="255" required placeholder="Récupération des recettes" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium flex items-center justify-center">
                <i class="ri-send-plane-fill mr-2"></i> Demander la récupération
            </button>
        </div>
    </form>

    @if($recettesEnAttente->isNotEmpty())
        <div class="mt-6 pt-4 border-t border-slate-200">
            <p class="text-sm font-semibold text-slate-700 mb-2">En attente de validation par les boutiques</p>
            <div class="flex flex-wrap gap-2">
                @foreach($recettesEnAttente as $r)
                    <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-3 py-1 text-xs font-semibold">
                        <i class="ri-time-line mr-1"></i> {{ $r->boutique?->nom ?? '—' }} : {{ number_format($r->amount, 0, ',', ' ') }} {{ param("currency") }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Approvisionnement du solde personnel : simple ou sous forme de dette --}}
    <div class="glass-panel rounded-2xl p-6" x-data="{ mode: 'simple' }">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center">
            <i class="ri-hand-coin-line text-2xl text-emerald-600 mr-2"></i> Approvisionner mon solde
        </h2>
        <p class="text-sm text-slate-500 mb-4">Versez du cash sur votre solde personnel, définitivement ou sous forme de dette à rembourser.</p>

        <form action="{{ route('admin.solde.crediter') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Montant ({{ param("currency") }})</label>
                <input type="number" name="montant" min="1" step="1" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-2 gap-2">
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="simple" x-model="mode" class="sr-only" checked>
                    <div class="p-3 rounded-lg border-2 text-center transition-colors"
                        :class="mode === 'simple' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-slate-200 text-slate-500'">
                        <i class="ri-cash-line block text-xl mb-1"></i>
                        <span class="text-xs font-bold">Simple</span>
                        <span class="block text-[10px]">Rien à rembourser</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="mode" value="dette" x-model="mode" class="sr-only">
                    <div class="p-3 rounded-lg border-2 text-center transition-colors"
                        :class="mode === 'dette' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-slate-200 text-slate-500'">
                        <i class="ri-scales-3-line block text-xl mb-1"></i>
                        <span class="text-xs font-bold">Dette</span>
                        <span class="block text-[10px]">À rembourser molo molo</span>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Motif</label>
                <input type="text" name="motif" maxlength="255" required placeholder="Approvisionnement en cash"
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <p class="text-xs text-slate-400" x-show="mode === 'dette'" x-cloak>
                <i class="ri-information-line"></i> Votre solde est crédité immédiatement, et une avance s'ouvre : vous la remboursez progressivement depuis ce même solde.
            </p>

            <button type="submit" class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-medium">
                <i class="ri-add-line mr-1"></i> Créditer mon solde
            </button>
        </form>
    </div>

    {{-- Retrait --}}
    <div class="glass-panel rounded-2xl p-6">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center">
            <i class="ri-share-forward-box-line text-2xl text-rose-600 mr-2"></i> Vider mon solde
        </h2>
        <p class="text-sm text-slate-500 mb-4">Retirez tout ou partie de votre solde personnel.</p>

        <form action="{{ route('admin.solde.retirer') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Montant à retirer ({{ param("currency") }})</label>
                <input type="number" name="montant" min="1" max="{{ (int) $admin->solde_personnel }}" step="1" required
                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Motif</label>
                <input type="text" name="motif" maxlength="255" placeholder="Retrait du solde personnel" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-rose-500">
            </div>
            <button type="submit" onclick="return confirm('Confirmer ce retrait de votre solde personnel ?')"
                class="w-full px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 font-medium">
                <i class="ri-subtract-line mr-1"></i> Retirer
            </button>
        </form>
    </div>

    {{-- Dettes imputées à mon solde --}}
    <div class="glass-panel rounded-2xl p-6">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center">
            <i class="ri-bill-line text-2xl text-amber-600 mr-2"></i> Mes achats à crédit
        </h2>
        <p class="text-sm text-slate-500 mb-4">Achats à crédit imputés à votre solde personnel.</p>

        @forelse($dettesAdmin as $achat)
            <div class="p-3 mb-3 rounded-xl border border-amber-100 bg-white">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <div class="text-sm">
                        <div class="font-semibold text-slate-800">#{{ $achat->id }} — {{ $achat->fournisseur?->nom ?? 'Fournisseur' }}</div>
                        <div class="text-xs text-slate-500">Reste : <strong class="text-rose-600">{{ number_format($achat->reste_a_payer, 0, ',', ' ') }} {{ param("currency") }}</strong> sur {{ number_format($achat->montant_total, 0, ',', ' ') }}</div>
                    </div>
                </div>
                <form action="{{ route('admin.solde.achats.rembourser', $achat) }}" method="POST" class="flex items-end gap-2">
                    @csrf
                    <input type="number" name="montant" min="1" step="1" max="{{ (int) $achat->reste_a_payer }}"
                        value="{{ (int) min($admin->solde_personnel, $achat->reste_a_payer) }}" required
                        class="w-32 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700">Rembourser</button>
                </form>
            </div>
        @empty
            <p class="text-sm text-slate-400">Aucun achat à crédit imputé à votre solde.</p>
        @endforelse
    </div>
</div>

{{-- Avances personnelles à rembourser --}}
@if($avancesAdmin->isNotEmpty())
    <div class="glass-panel rounded-2xl p-6 mb-8 border-l-4 border-amber-500">
        <div class="flex items-baseline justify-between gap-4 mb-1">
            <h2 class="text-xl font-bold text-slate-800 flex items-center">
                <i class="ri-scales-3-line text-2xl text-amber-600 mr-2"></i> Mes avances à rembourser
            </h2>
            <span class="text-xl font-black text-amber-600">{{ number_format($avancesRestant, 0, ',', ' ') }} {{ param("currency") }}</span>
        </div>
        <p class="text-sm text-slate-500 mb-4">Cash crédité sur votre solde sous forme de dette. Remboursez progressivement, selon ce que vous avez en caisse.</p>

        <div class="space-y-3">
            @foreach($avancesAdmin as $avance)
                <div class="p-4 bg-white/50 border border-amber-200 rounded-xl">
                    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
                        <div>
                            <div class="font-bold text-slate-800">{{ $avance->motif }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                Avance du {{ $avance->created_at->translatedFormat('d/m/Y') }} —
                                {{ number_format($avance->montant, 0, ',', ' ') }} {{ param("currency") }} au total,
                                déjà remboursé {{ number_format($avance->montant_rembourse, 0, ',', ' ') }}.
                            </div>
                            <div class="text-sm font-black text-amber-700 mt-1">
                                Reste : {{ number_format($avance->reste_a_rembourser, 0, ',', ' ') }} {{ param("currency") }}
                            </div>
                        </div>

                        <form action="{{ route('admin.solde.avances.rembourser', $avance) }}" method="POST" class="flex items-end gap-2 shrink-0">
                            @csrf
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Montant</label>
                                <input type="number" name="montant" min="1" step="1"
                                    max="{{ (int) min($avance->reste_a_rembourser, (float) $admin->solde_personnel) }}"
                                    value="{{ (int) min($avance->reste_a_rembourser, (float) $admin->solde_personnel) }}" required
                                    class="w-32 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            </div>
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium">
                                Rembourser
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Historique --}}
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-white/40">
        <h2 class="text-xl font-bold text-slate-800">Historique des mouvements</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Date</th>
                    <th class="p-4 font-semibold">Type</th>
                    <th class="p-4 font-semibold">Motif</th>
                    <th class="p-4 font-semibold">Point de vente</th>
                    <th class="p-4 font-semibold text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($mouvements as $m)
                    <tr class="border-b border-white/20 hover:bg-white/30">
                        <td class="p-4 text-slate-600 whitespace-nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $m->est_entree ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $m->type_label }}</span>
                        </td>
                        <td class="p-4 text-slate-700">{{ $m->motif ?? '—' }}</td>
                        <td class="p-4 text-slate-600">{{ $m->boutique?->nom ?? '—' }}</td>
                        <td class="p-4 text-right font-bold whitespace-nowrap {{ $m->est_entree ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $m->est_entree ? '+' : '−' }} {{ number_format(abs($m->montant), 0, ',', ' ') }} {{ param("currency") }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-12 text-center text-slate-500">Aucun mouvement pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $mouvements->links() }}</div>
@endsection
