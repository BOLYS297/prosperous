@extends('layouts.magasinier')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Espace Magasinier</h2>
    <p class="text-black">Boutique : <span class="font-bold">{{ $boutique->nom }}</span></p>
</div>

@if(isset($shiftWarning) && $shiftWarning)
    <div class="mb-6 rounded-2xl bg-amber-100 border border-amber-200 p-5 shadow-sm text-amber-900">
        <div class="flex items-start gap-3">
            <div class="mt-0.5">
                <i class="ri-time-line text-3xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-lg">Fin de tranche horaire imminente</h3>
                <p class="text-sm text-amber-700 mt-1">Votre tranche se termine à <strong>{{ $shiftWarning['end'] }}</strong>. Il reste <strong>{{ $shiftWarning['minutes'] }} min {{ $shiftWarning['seconds'] }} s</strong>.</p>
                <p class="text-xs text-amber-700 mt-1">Sauvegardez vos actions et préparez-vous à clôturer votre session.</p>
            </div>
        </div>
    </div>
@endif

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
        <i class="ri-checkbox-circle-line text-lg mr-2"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Stat 1 -->
    <div class="glass-panel p-6 rounded-2xl flex items-center hover:bg-white/80 transition-colors">
        <div class="p-4 bg-emerald-100 text-emerald-600 rounded-xl mr-4 shadow-sm border border-emerald-200">
            <i class="ri-archive-line text-3xl"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500 mb-1">Produits en Stock</div>
            <div class="text-3xl font-black text-slate-800">{{ $totalProduits }}</div>
        </div>
    </div>

    <!-- Stat 2 -->
    <div class="glass-panel p-6 rounded-2xl flex items-center hover:bg-white/80 transition-colors">
        <div class="p-4 bg-amber-100 text-amber-600 rounded-xl mr-4 shadow-sm border border-amber-200">
            <i class="ri-error-warning-line text-3xl"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500 mb-1">En Rupture</div>
            <div class="text-3xl font-black text-slate-800">{{ $ruptures }}</div>
        </div>
    </div>

    <!-- Stat 3 -->
    <div class="glass-panel p-6 rounded-2xl flex items-center hover:bg-white/80 transition-colors">
        <div class="p-4 bg-rose-100 text-rose-600 rounded-xl mr-4 shadow-sm border border-rose-200">
            <i class="ri-delete-bin-line text-3xl"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500 mb-1">Pertes ce Mois</div>
            <div class="text-3xl font-black text-slate-800">{{ $pertesMois }}</div>
        </div>
    </div>
</div>

<div class="glass-panel rounded-2xl p-6 bg-white mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Recharges en attente</h3>
            <p class="text-sm text-slate-500">Traitement des livraisons fournisseurs vers le magasin.</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">{{ $rechargeCount }} en attente</span>
    </div>

    @if($recharges->isEmpty())
        <p class="text-slate-500">Aucune recharge en attente pour le moment.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700 text-sm uppercase tracking-wide">
                        <th class="px-4 py-3 border border-slate-200">Recharge</th>
                        <th class="px-4 py-3 border border-slate-200">Produit</th>
                        <th class="px-4 py-3 border border-slate-200">Quantité attendue</th>
                        <th class="px-4 py-3 border border-slate-200">Fournisseur</th>
                        <th class="px-4 py-3 border border-slate-200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recharges as $recharge)
                        @foreach($recharge->lignes as $ligne)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 border border-slate-200 text-sm">#{{ $recharge->id }}<br><span class="text-xs text-slate-500">{{ $recharge->created_at->format('d/m/Y') }}</span></td>
                                <td class="px-4 py-3 border border-slate-200 text-sm">{{ $ligne->produit->nom ?? 'Produit supprimé' }}@if($ligne->produit && $ligne->produit->reference) ({{ $ligne->produit->reference }})@endif</td>
                                <td class="px-4 py-3 border border-slate-200 text-sm">{{ $ligne->quantite_envoyee }}</td>
                                <td class="px-4 py-3 border border-slate-200 text-sm">{{ $recharge->fournisseur?->nom ?? '-' }}</td>
                                <td class="px-4 py-3 border border-slate-200 text-sm">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ route('magasinier.recharges.show', $recharge) }}" class="px-3 py-2 bg-blue-600 text-white rounded text-sm">Traiter</a>
                                        <a href="{{ route('magasinier.recharges.show', $recharge) }}#probleme" class="px-3 py-2 bg-rose-600 text-white rounded text-sm">Signaler</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="{{ route('magasinier.stocks.index') }}" class="glass-panel p-8 rounded-2xl flex flex-col items-center justify-center text-center hover:bg-white/80 transition-colors group">
        <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-md">
            <i class="ri-list-check-2 text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-slate-800 mb-2">Consulter le Stock</h3>
        <p class="text-slate-500 text-sm">Voir tous les produits disponibles dans votre magasin.</p>
    </a>

    <a href="{{ route('magasinier.depenses.create') }}" class="glass-panel p-8 rounded-2xl flex flex-col items-center justify-center text-center hover:bg-white/80 transition-colors group">
        <div class="w-20 h-20 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform shadow-md">
            <i class="ri-file-warning-line text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-slate-800 mb-2">Déclarer une perte de stock</h3>
        <p class="text-slate-500 text-sm">Signalez une marchandise endommagée ou perdue sur le stock du magasin.</p>
    </a>
</div>

@endsection
