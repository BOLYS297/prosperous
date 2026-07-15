@extends('layouts.admin')

@section('content')
@php
    $libelles = [
        'pending' => ['label' => 'En attente admin', 'class' => 'bg-amber-100 text-amber-700'],
        'attente_boutique' => ['label' => 'Attente boutique', 'class' => 'bg-blue-100 text-blue-700'],
        'approved' => ['label' => 'Validée', 'class' => 'bg-emerald-100 text-emerald-700'],
        'rejected' => ['label' => 'Rejetée', 'class' => 'bg-rose-100 text-rose-700'],
    ];
@endphp

<div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-end gap-4">
    <div>
        <h1 class="text-3xl font-bold text-primary mb-2 tracking-tight">Historique des dépenses</h1>
        <p class="text-black">Toutes les dépenses (administratives et déclarées par les vendeurs). Vous pouvez les modifier ou les supprimer.</p>
    </div>
    <a href="{{ route('admin.depenses.create') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center shrink-0">
        <i class="ri-add-line mr-2"></i> Nouvelle dépense
    </a>
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="glass-panel rounded-2xl p-5">
        <div class="text-sm text-slate-500 mb-1">Total validé</div>
        <div class="text-2xl font-black text-slate-900">{{ number_format($totalApprouve, 0, ',', ' ') }} {{ param("currency") }}</div>
    </div>
    <div class="glass-panel rounded-2xl p-5">
        <div class="text-sm text-slate-500 mb-1">En attente admin</div>
        <div class="text-2xl font-black text-amber-600">{{ $counts['pending'] }}</div>
    </div>
    <div class="glass-panel rounded-2xl p-5">
        <div class="text-sm text-slate-500 mb-1">Attente boutique</div>
        <div class="text-2xl font-black text-blue-600">{{ $counts['attente_boutique'] }}</div>
    </div>
</div>

<div class="glass-panel rounded-2xl overflow-hidden">
    <form action="{{ route('admin.depenses.index') }}" method="GET" class="p-6 border-b border-white/40 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Statut</label>
            <select name="statut" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-sm">
                <option value="">Tous</option>
                @foreach($libelles as $key => $l)
                    <option value="{{ $key }}" {{ $statut === $key ? 'selected' : '' }}>{{ $l['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Boutique</label>
            <select name="boutique_id" class="px-3 py-2 border border-slate-300 rounded-lg bg-white text-sm">
                <option value="">Toutes</option>
                @foreach($boutiques as $b)
                    <option value="{{ $b->id }}" {{ (string) $boutiqueId === (string) $b->id ? 'selected' : '' }}>{{ $b->nom }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">Filtrer</button>
        @if($statut !== '' || $boutiqueId !== '')
            <a href="{{ route('admin.depenses.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-100 text-sm">Effacer</a>
        @endif
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Date</th>
                    <th class="p-4 font-semibold">Dépense</th>
                    <th class="p-4 font-semibold">Boutique</th>
                    <th class="p-4 font-semibold">Déclarée par</th>
                    <th class="p-4 font-semibold text-right">Montant</th>
                    <th class="p-4 font-semibold">Statut</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($depenses as $depense)
                    @php $l = $libelles[$depense->statut] ?? ['label' => $depense->statut, 'class' => 'bg-slate-100 text-slate-700']; @endphp
                    <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4 text-slate-600 whitespace-nowrap">{{ $depense->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4">
                            <div class="font-bold text-slate-800">{{ $depense->intitule }}</div>
                            @if($depense->description)
                                <div class="text-xs text-slate-500 mt-1 max-w-md">{{ \Illuminate\Support\Str::limit($depense->description, 90) }}</div>
                            @endif
                        </td>
                        <td class="p-4 text-slate-600">{{ $depense->boutique?->nom ?? '—' }}</td>
                        <td class="p-4 text-slate-600">{{ $depense->user?->nom_utilisateur ?? '—' }}</td>
                        <td class="p-4 text-right font-bold text-slate-800 whitespace-nowrap">{{ number_format($depense->montant, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $l['class'] }}">{{ $l['label'] }}</span>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.depenses.edit', $depense) }}" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition-colors" title="Modifier">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <form action="{{ route('admin.depenses.destroy', $depense) }}" method="POST" onsubmit="return confirm('Supprimer cette dépense ?{{ $depense->statut === 'approved' ? ' Le solde de la boutique sera re-crédité.' : '' }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition-colors" title="Supprimer">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center text-slate-500">Aucune dépense pour ces critères.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $depenses->links() }}
</div>
@endsection
