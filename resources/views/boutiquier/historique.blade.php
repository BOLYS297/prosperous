@extends('layouts.boutiquier')

@section('content')
<div class="mb-8">
    <a href="{{ route('boutiquier.dashboard') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à la caisse
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Historique des Ventes du Jour</h2>
    <p class="text-black">{{ now()->translatedFormat('l d F Y') }}</p>
</div>

<!-- Total du jour -->
<div class="glass-panel p-6 rounded-2xl mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <div class="p-4 bg-emerald-100 text-emerald-600 rounded-xl mr-4 shadow-sm border border-emerald-200">
            <i class="ri-wallet-3-line text-3xl"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500">Total des recettes du jour</div>
            <div class="text-3xl font-black text-slate-800">{{ number_format($totalJour, 0, ',', ' ') }} <span class="text-sm font-medium text-slate-500">FCFA</span></div>
        </div>
    </div>
    <div class="text-5xl font-black text-emerald-200">
        <i class="ri-line-chart-line"></i>
    </div>
</div>

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 bg-white/50 border-b border-slate-200/50">
        <h3 class="font-bold text-slate-800">Détail des ventes</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Heure</th>
                    <th class="p-4 font-semibold">Produit(s)</th>
                    <th class="p-4 font-semibold text-center">Qté</th>
                    <th class="p-4 font-semibold text-right">Montant</th>
                    <th class="p-4 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($ventes as $vente)
                    @foreach($vente->lignes as $ligne)
                    <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4 text-slate-500">
                            <i class="ri-time-line mr-1"></i>{{ $vente->created_at->format('H:i') }}
                        </td>
                        <td class="p-4 font-bold text-slate-800">
                            {{ $ligne->produit->nom ?? '—' }}
                            @if($ligne->produit && $ligne->produit->reference)
                                <div class="text-xs text-slate-500 font-mono mt-1">{{ $ligne->produit->reference }}</div>
                            @endif
                        </td>
                        <td class="p-4 text-center font-bold text-slate-700">
                            {{ $ligne->quantite }}
                        </td>
                        <td class="p-4 text-right font-black text-blue-600">
                            {{ number_format($ligne->quantite * $ligne->prix_unitaire, 0, ',', ' ') }} FCFA
                        </td>
                        @if($loop->first)
                            <td class="p-4 text-center" rowspan="{{ $vente->lignes->count() }}">
                                <a href="{{ route('boutiquier.ventes.show', $vente) }}" class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class=""></i> Actions
                                </a>
                            </td>
                        @endif
                    </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-500">
                            <i class="ri-inbox-line text-4xl block mb-2"></i>
                            Aucune vente enregistrée aujourd'hui.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
