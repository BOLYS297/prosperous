@extends('layouts.magasinier')

@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-primary">Recharges en attente</h2>
    <p class="text-sm text-black">Liste des recharges fournisseurs à vérifier et confirmer.</p>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
        {{ session('success') }}
    </div>
@endif

<div class="glass-panel rounded-2xl p-6 bg-white">
    @forelse($recharges as $recharge)
        <div class="p-4 border-b flex justify-between items-center">
            <div>
                <div class="font-semibold">Fournisseur: {{ $recharge->fournisseur?->nom ?? '-' }}</div>
                <div class="text-sm text-slate-500">Créée: {{ $recharge->created_at->diffForHumans() }}</div>
                <div class="text-sm mt-2">Lignes: {{ $recharge->lignes->count() }} produits</div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('magasinier.recharges.show', $recharge) }}" class="px-4 py-2 bg-blue-600 text-white rounded">Détails</a>
            </div>
        </div>
    @empty
        <p class="text-slate-500">Aucune recharge en attente.</p>
    @endforelse
</div>

@endsection
