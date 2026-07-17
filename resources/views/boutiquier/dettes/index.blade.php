@extends('layouts.boutiquier')

@section('content')
<div class="space-y-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Recouvrement des dettes</h2>
            <p class="text-black">Toutes les dettes fournisseurs en cours sont suivies ici. Payez une partie depuis le solde de votre boutique.</p>
        </div>
        <div class="glass-panel p-5 rounded-2xl bg-white shadow-sm border border-slate-200">
            <div class="text-sm text-slate-500">Solde boutique</div>
            <div class="text-3xl font-black text-slate-800">{{ number_format($boutique->solde ?? 0, 0, ',', ' ') }} {{ param("currency") }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center animate-pulse">
            <i class="ri-checkbox-circle-line text-lg mr-2"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center">
            <i class="ri-error-warning-line text-lg mr-2"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="glass-panel rounded-2xl p-6 bg-white shadow-sm">
            <div class="text-sm text-slate-500 mb-2">Dettes en cours</div>
            <div class="text-4xl font-black text-slate-800">{{ $dettes->count() }}</div>
        </div>
        <div class="glass-panel rounded-2xl p-6 bg-white shadow-sm">
            <div class="text-sm text-slate-500 mb-2">Montant total restant</div>
            <div class="text-4xl font-black text-slate-800">{{ number_format($montantTotalRestant, 0, ',', ' ') }} {{ param("currency") }}</div>
        </div>
        <div class="glass-panel rounded-2xl p-6 bg-white shadow-sm">
            <div class="text-sm text-slate-500 mb-2">Dernière mise à jour</div>
            <div class="text-4xl font-black text-slate-800">{{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    @if($dettes->isEmpty())
        <div class="glass-panel rounded-2xl p-8 text-center text-slate-500">
            <i class="ri-money-dollar-box-line text-5xl mb-4"></i>
            <p>Aucune dette en cours pour le moment.</p>
        </div>
    @else
        <div class="glass-panel rounded-2xl overflow-hidden">
            <div class="p-6 bg-white/70 border-b border-slate-200/50">
                <h3 class="font-bold text-slate-800">Dettes fournisseurs ouvertes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-sm uppercase tracking-wider">
                            <th class="p-4">Fournisseur</th>
                            <th class="p-4 text-center">Montant total</th>
                            <th class="p-4 text-center">Payé</th>
                            <th class="p-4 text-center">Restant</th>
                            <th class="p-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($dettes as $achat)
                            <tr class="border-b border-slate-200 hover:bg-slate-50">
                                <td class="p-4 font-medium text-slate-800">
                                    {{ $achat->fournisseur?->nom ?? 'Fournisseur inconnu' }}
                                    <div class="mt-1">
                                        @if($achat->debit_boutique_id)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-[11px] font-semibold">
                                                <i class="ri-store-2-line mr-1"></i> Attribuée à {{ $achat->debitBoutique?->nom ?? 'une boutique' }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-600 px-2 py-0.5 text-[11px] font-semibold">
                                                <i class="ri-group-line mr-1"></i> Partagée
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 text-center text-slate-600">{{ number_format($achat->montant_total, 0, ',', ' ') }} {{ param("currency") }}</td>
                                <td class="p-4 text-center text-emerald-700 font-semibold">{{ number_format($achat->montant_paye, 0, ',', ' ') }} {{ param("currency") }}</td>
                                <td class="p-4 text-center text-rose-700 font-semibold">{{ number_format($achat->reste_a_payer, 0, ',', ' ') }} {{ param("currency") }}</td>
                                <td class="p-4">
                                    <form action="{{ route('boutiquier.dettes.payer', $achat) }}" method="POST" data-offline-sync="true" class="space-y-3">
                                        @csrf
                                        <label class="block text-slate-600 text-sm">Montant à payer</label>
                                        <input name="montant" type="number" min="1" step="1" max="{{ (int) $achat->reste_a_payer }}" value="{{ (int) min($boutique->solde, $achat->reste_a_payer) }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none" required>
                                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors">Payer</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($avances->isNotEmpty())
        <div class="glass-panel rounded-2xl overflow-hidden">
            <div class="p-6 bg-amber-50/70 border-b border-amber-200/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-800"><i class="ri-hand-coin-line mr-1 text-amber-600"></i> Avances de caisse à rembourser</h3>
                    <p class="text-sm text-slate-500">Avances accordées par l'administrateur, à rembourser progressivement depuis votre solde.</p>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-xs text-slate-500">Total restant</div>
                    <div class="text-xl font-black text-amber-700">{{ number_format($avancesRestant, 0, ',', ' ') }} {{ param("currency") }}</div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-sm uppercase tracking-wider">
                            <th class="p-4">Avance</th>
                            <th class="p-4 text-center">Montant</th>
                            <th class="p-4 text-center">Remboursé</th>
                            <th class="p-4 text-center">Restant</th>
                            <th class="p-4 text-center">Rembourser</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($avances as $avance)
                            <tr class="border-b border-slate-200 hover:bg-slate-50">
                                <td class="p-4 font-medium text-slate-800">
                                    {{ $avance->motif ?? 'Avance de caisse' }}
                                    <div class="text-xs text-slate-400 mt-1">{{ $avance->created_at->format('d/m/Y') }} · {{ $avance->admin?->nom_utilisateur ?? 'Administrateur' }}</div>
                                </td>
                                <td class="p-4 text-center text-slate-600">{{ number_format($avance->montant, 0, ',', ' ') }} {{ param("currency") }}</td>
                                <td class="p-4 text-center text-emerald-700 font-semibold">{{ number_format($avance->montant_rembourse, 0, ',', ' ') }} {{ param("currency") }}</td>
                                <td class="p-4 text-center text-rose-700 font-semibold">{{ number_format($avance->reste_a_rembourser, 0, ',', ' ') }} {{ param("currency") }}</td>
                                <td class="p-4">
                                    <form action="{{ route('boutiquier.avances.rembourser', $avance) }}" method="POST" data-offline-sync="true" class="space-y-3">
                                        @csrf
                                        <label class="block text-slate-600 text-sm">Montant à rembourser</label>
                                        <input name="montant" type="number" min="1" step="1" max="{{ (int) $avance->reste_a_rembourser }}" value="{{ (int) min($boutique->solde ?? 0, $avance->reste_a_rembourser) }}" class="w-full px-3 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-amber-500 outline-none" required>
                                        <button type="submit" class="w-full px-4 py-2 bg-amber-600 text-white rounded-xl font-semibold hover:bg-amber-700 transition-colors">Rembourser</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
