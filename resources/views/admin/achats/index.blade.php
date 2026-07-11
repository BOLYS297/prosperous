@extends('layouts.admin')

@section('content')
<div class="mb-8 flex justify-between items-end">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Historique des Achats</h2>
        <p class="text-black">Suivez le réapprovisionnement et l'état des dettes fournisseurs.</p>
    </div>
    <a href="{{ route('admin.achats.create') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center">
        <i class="ri-shopping-cart-2-line mr-2"></i> Nouvel Achat
    </a>
</div>

<form action="{{ route('admin.achats.index') }}" method="GET" class="mb-6 grid gap-4 md:grid-cols-[1fr_auto] items-end">
    <div>
        <label for="q" class="sr-only">Recherche achats</label>
        <input id="q" name="q" type="text" value="{{ old('q', $q ?? '') }}" autocomplete="off" data-instant-search="#achats-tbody" data-instant-search-empty="#achats-empty" placeholder="Rechercher achat, fournisseur, boutique ou statut..." class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none" />
    </div>
    <div class="flex items-center gap-3">
        <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition-colors">Chercher</button>
        @if(!empty($q))
            <a href="{{ route('admin.achats.index') }}" class="px-5 py-3 border border-slate-300 rounded-2xl text-slate-700 hover:bg-slate-100 transition-colors">Effacer</a>
        @endif
    </div>
</form>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
        <i class="ri-checkbox-circle-line text-lg mr-2"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Référence / Date</th>
                    <th class="p-4 font-semibold">Fournisseur</th>
                    <th class="p-4 font-semibold">Boutique Dest.</th>
                    <th class="p-4 font-semibold">Montant Total</th>
                    <th class="p-4 font-semibold">Statut</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="achats-tbody" class="text-sm">
                @forelse($achats as $achat)
                    @php
                        $hasSupplierDebt = $achat->recharge && $achat->recharge->lignes->sum('quantite_manquante') > 0;
                    @endphp
                    <tr data-search="{{ \Illuminate\Support\Str::lower('achat-' . str_pad($achat->id, 4, '0', STR_PAD_LEFT) . ' ' . ($achat->fournisseur->nom ?? '') . ' ' . ($achat->boutique->nom ?? '') . ' ' . ($achat->statut === 'paye' ? 'paye payé' : 'dette')) }}" class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4 font-medium text-slate-800">
                            #ACHAT-{{ str_pad($achat->id, 4, '0', STR_PAD_LEFT) }}
                            @if($hasSupplierDebt)
                                <span class="ml-2 inline-flex items-center text-xs font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full" title="Dette fournisseur détectée">
                                    <i class="ri-error-warning-line mr-1"></i> Dette fournisseur
                                </span>
                            @elseif($achat->recharge && $achat->recharge->statut === 'anomalie')
                                <span class="ml-2 inline-flex items-center text-xs font-bold text-rose-600 bg-rose-100 px-2 py-0.5 rounded-full" title="Anomalie signalée lors de la réception">
                                    <i class="ri-alert-fill mr-1"></i> Anomalie
                                </span>
                            @endif
                            <div class="text-xs text-slate-500 font-normal">{{ $achat->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="p-4 text-slate-800 font-medium">
                            {{ $achat->fournisseur ? $achat->fournisseur->nom : 'Fournisseur supprimé' }}
                        </td>
                        <td class="p-4 text-slate-600">
                            {{ $achat->boutique ? $achat->boutique->nom : '-' }}
                        </td>
                        <td class="p-4 font-bold text-slate-800">
                            {{ number_format($achat->montant_total, 0, ',', ' ') }} {{ param("currency") }}
                        </td>
                        <td class="p-4">
                            @php $validation = $achatValidations[$achat->id] ?? null; @endphp
                            @if($achat->statut === 'paye')
                                @if($validation && $validation->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700" title="Le boutiquier doit valider le paiement avant débit de son solde">
                                        <i class="ri-hourglass-line mr-1"></i> Paiement à valider
                                    </span>
                                @elseif($validation && $validation->status === 'rejected')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700" title="Paiement contesté par la boutique — aucun débit appliqué">
                                        <i class="ri-close-circle-line mr-1"></i> Paiement contesté
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                        <i class="ri-check-line mr-1"></i> Payé
                                    </span>
                                @endif
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                    <i class="ri-time-line mr-1"></i> Dette
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.achats.show', $achat) }}" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition-colors inline-block" title="Voir les détails">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('admin.achats.edit', $achat) }}" class="p-2 bg-amber-100 text-amber-700 hover:bg-amber-200 rounded-lg transition-colors inline-block" title="Modifier l'achat">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <form action="{{ route('admin.achats.destroy', $achat) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cet achat ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-100 text-rose-700 hover:bg-rose-200 rounded-lg transition-colors" title="Supprimer l'achat">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <i class="ri-shopping-cart-2-line text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-800">Aucun achat enregistré</h3>
                            <p class="text-slate-500 mt-1">Vous n'avez pas encore effectué d'achats auprès de vos fournisseurs.</p>
                        </td>
                    </tr>
                @endforelse
                <tr id="achats-empty" style="display:none">
                    <td colspan="6" class="p-12 text-center text-slate-500">Aucun achat ne correspond à votre recherche.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
