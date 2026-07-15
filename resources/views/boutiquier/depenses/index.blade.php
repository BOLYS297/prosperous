@extends('layouts.boutiquier')

@section('content')
@php
    $libelles = [
        'pending' => ['label' => 'En attente de validation', 'class' => 'bg-amber-100 text-amber-700'],
        'attente_boutique' => ['label' => 'Débit à valider', 'class' => 'bg-blue-100 text-blue-700'],
        'approved' => ['label' => 'Validée', 'class' => 'bg-emerald-100 text-emerald-700'],
        'rejected' => ['label' => 'Rejetée', 'class' => 'bg-rose-100 text-rose-700'],
    ];
@endphp

<div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Mes dépenses</h2>
        <p class="text-black">Historique de vos dépenses déclarées. Vous pouvez modifier ou supprimer celles qui ne sont pas encore traitées.</p>
    </div>
    <a href="{{ route('boutiquier.depenses.create') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center shrink-0">
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

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600 uppercase tracking-wider">
                    <th class="p-4">Date</th>
                    <th class="p-4">Dépense</th>
                    <th class="p-4 text-right">Montant</th>
                    <th class="p-4">Statut</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($depenses as $depense)
                    @php
                        $l = $libelles[$depense->statut] ?? ['label' => $depense->statut, 'class' => 'bg-slate-100 text-slate-700'];
                        $modifiable = $depense->statut === 'pending';
                    @endphp
                    <tr class="border-b border-slate-200 hover:bg-slate-50">
                        <td class="p-4 text-slate-600 whitespace-nowrap">{{ $depense->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4">
                            <div class="font-bold text-slate-800">{{ $depense->intitule }}</div>
                            @if($depense->description)
                                <div class="text-xs text-slate-500 mt-1 max-w-md">{{ \Illuminate\Support\Str::limit($depense->description, 90) }}</div>
                            @endif
                        </td>
                        <td class="p-4 text-right font-bold text-slate-800 whitespace-nowrap">{{ number_format($depense->montant, 0, ',', ' ') }} {{ param("currency") }}</td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $l['class'] }}">{{ $l['label'] }}</span>
                        </td>
                        <td class="p-4 text-right">
                            @if($modifiable)
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('boutiquier.depenses.edit', $depense) }}" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition-colors" title="Modifier">
                                        <i class="ri-pencil-line"></i>
                                    </a>
                                    <form action="{{ route('boutiquier.depenses.destroy', $depense) }}" method="POST" data-offline-sync="true" onsubmit="return confirm('Supprimer cette dépense ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition-colors" title="Supprimer">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="inline-flex items-center text-xs text-slate-400" title="Déjà traitée par l'administrateur">
                                    <i class="ri-lock-line mr-1"></i> Verrouillée
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <i class="ri-wallet-3-line text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-800">Aucune dépense</h3>
                            <p class="text-slate-500 mt-1">Vos dépenses déclarées apparaîtront ici.</p>
                        </td>
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
