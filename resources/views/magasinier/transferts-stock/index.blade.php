@extends('layouts.magasinier')

@section('content')
@php
    $badges = [
        'en_attente_source' => 'bg-amber-100 text-amber-700',
        'autorise' => 'bg-blue-100 text-blue-700',
        'recu' => 'bg-emerald-100 text-emerald-700',
        'refuse' => 'bg-slate-200 text-slate-700',
        'probleme' => 'bg-rose-100 text-rose-700',
    ];
@endphp

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Transferts entre points de vente</h2>
        <p class="text-black">Déplacez du stock d'un point de vente vers un autre. Chaque étape est validée par le vendeur concerné.</p>
    </div>
    <a href="{{ route('magasinier.transferts-stock.create') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center shrink-0">
        <i class="ri-add-line mr-2"></i> Nouveau transfert
    </a>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
        <i class="ri-checkbox-circle-line text-lg mr-2"></i><span>{{ session('success') }}</span>
    </div>
@endif

<div class="glass-panel rounded-2xl overflow-hidden bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600 uppercase tracking-wider">
                    <th class="p-4">Date</th>
                    <th class="p-4">Produit</th>
                    <th class="p-4">Trajet</th>
                    <th class="p-4 text-center">Demandé</th>
                    <th class="p-4 text-center">Autorisé</th>
                    <th class="p-4 text-center">Reçu</th>
                    <th class="p-4">Statut</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($transferts as $t)
                    <tr class="border-b border-slate-200 hover:bg-slate-50">
                        <td class="p-4 text-slate-600 whitespace-nowrap">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4 font-semibold text-slate-800">
                            {{ $t->produit?->nom ?? 'Produit supprimé' }}
                            @if($t->produit?->reference)
                                <span class="text-xs text-slate-400 font-mono">({{ $t->produit->reference }})</span>
                            @endif
                        </td>
                        <td class="p-4 text-slate-600">
                            <span class="font-medium">{{ $t->source?->nom ?? '—' }}</span>
                            <i class="ri-arrow-right-line mx-1 text-slate-400"></i>
                            <span class="font-medium">{{ $t->destination?->nom ?? '—' }}</span>
                        </td>
                        <td class="p-4 text-center font-bold text-slate-700">{{ $t->quantite_demandee }}</td>
                        <td class="p-4 text-center font-bold {{ $t->quantite_autorisee === null ? 'text-slate-300' : 'text-blue-700' }}">{{ $t->quantite_autorisee ?? '—' }}</td>
                        <td class="p-4 text-center font-bold {{ $t->quantite_recue === null ? 'text-slate-300' : 'text-emerald-700' }}">{{ $t->quantite_recue ?? '—' }}</td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $badges[$t->statut] ?? 'bg-slate-100 text-slate-700' }}">{{ $t->statut_label }}</span>
                            @if($t->note)
                                <div class="text-xs text-slate-500 mt-1">{{ $t->note }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <i class="ri-arrow-left-right-line text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-800">Aucun transfert</h3>
                            <p class="text-slate-500 mt-1">Créez un transfert pour déplacer du stock entre deux points de vente.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $transferts->links() }}
</div>
@endsection
