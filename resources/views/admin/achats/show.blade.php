@extends('layouts.admin')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <a href="{{ route('admin.achats.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
            <i class="ri-arrow-left-line mr-1"></i> Retour à l'historique
        </a>
        <h2 class="text-3xl font-bold text-primary tracking-tight">Détails de l'Achat #{{ str_pad($achat->id, 4, '0', STR_PAD_LEFT) }}</h2>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.achats.edit', $achat) }}" class="px-4 py-2 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 transition-colors flex items-center">
            <i class="ri-edit-line mr-2"></i> Modifier
        </a>
        <form action="{{ route('admin.achats.destroy', $achat) }}" method="POST" onsubmit="return confirm('Supprimer cet achat ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-rose-100 text-rose-700 rounded-lg hover:bg-rose-200 transition-colors flex items-center">
                <i class="ri-delete-bin-line mr-2"></i> Supprimer
            </button>
        </form>
        <button onclick="window.print()" class="px-4 py-2 bg-white/20 text-emerald-600 rounded-lg hover:bg-white/30 transition-colors flex items-center backdrop-blur-md">
            <i class="ri-printer-line mr-2"></i> Imprimer
        </button>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="glass-panel p-6 rounded-2xl">
        <div class="text-sm text-slate-500 mb-1">Fournisseur</div>
        <div class="font-bold text-lg text-slate-800">{{ $achat->fournisseur ? $achat->fournisseur->nom : 'N/A' }}</div>
        @if($achat->fournisseur && $achat->fournisseur->contact)
            <div class="text-sm text-slate-600 mt-1"><i class="ri-phone-line"></i> {{ $achat->fournisseur->contact }}</div>
        @endif
    </div>

    <div class="glass-panel p-6 rounded-2xl">
        <div class="text-sm text-slate-500 mb-1">Informations</div>
        <div class="font-medium text-slate-800"><i class="ri-calendar-line"></i> {{ $achat->created_at->format('d/m/Y à H:i') }}</div>
        <div class="text-sm text-slate-600 mt-1"><i class="ri-store-2-line"></i> Dest: {{ $achat->boutique ? $achat->boutique->nom : 'N/A' }}</div>
    </div>

    <div class="glass-panel p-6 rounded-2xl flex flex-col justify-center items-center text-center {{ $achat->statut === 'paye' ? 'border-b-4 border-emerald-500' : 'border-b-4 border-rose-500' }}">
        <div class="text-sm text-slate-500 mb-1">Statut Paiement</div>
        @if($achat->statut === 'paye')
            @if(isset($validation) && $validation->status === 'pending')
                <div class="font-bold text-2xl text-amber-600 flex items-center"><i class="ri-hourglass-line mr-2"></i> À VALIDER</div>
                <div class="text-xs text-slate-500 mt-1">En attente de validation par la boutique avant débit</div>
            @elseif(isset($validation) && $validation->status === 'rejected')
                <div class="font-bold text-2xl text-rose-600 flex items-center"><i class="ri-close-circle-line mr-2"></i> CONTESTÉ</div>
                <div class="text-xs text-slate-500 mt-1">Paiement contesté par la boutique — aucun débit appliqué</div>
            @else
                <div class="font-bold text-2xl text-emerald-600 flex items-center"><i class="ri-check-double-line mr-2"></i> PAYÉ</div>
            @endif
        @else
            <div class="font-bold text-2xl text-rose-600 flex items-center"><i class="ri-time-line mr-2"></i> DETTE</div>
        @endif
    </div>
</div>

@php
    $hasReceptionIssue = $achat->recharge && ($achat->recharge->statut === 'anomalie' || $achat->recharge->lignes->sum('quantite_manquante') > 0);
@endphp

<div class="glass-panel rounded-2xl overflow-hidden mb-8">
    <div class="p-6 bg-white/50 border-b border-slate-200/50">
        <h3 class="font-bold text-slate-800">Produits achetés</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-slate-200/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Produit</th>
                    <th class="p-4 font-semibold text-center">Quantité Achetée</th>
                    @if($hasReceptionIssue)
                        <th class="p-4 font-semibold text-center text-rose-600">Reçu / Manquant</th>
                    @endif
                    <th class="p-4 font-semibold text-right">Prix Unitaire</th>
                    <th class="p-4 font-semibold text-right">Total</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($achat->lignes as $ligne)
                    <tr class="border-b border-white/20 hover:bg-white/30">
                        <td class="p-4 font-medium text-slate-800">
                            {{ $ligne->produit ? $ligne->produit->nom : 'Produit inconnu' }}@if($ligne->produit && $ligne->produit->reference) ({{ $ligne->produit->reference }})@endif
                        </td>
                        <td class="p-4 text-center text-slate-600 font-bold">
                            {{ $ligne->quantite }}
                        </td>
                        @if($hasReceptionIssue)
                            @php
                                $rechargeLigne = $achat->recharge->lignes->firstWhere('produit_id', $ligne->produit_id);
                            @endphp
                            <td class="p-4 text-center">
                                @if($rechargeLigne && $rechargeLigne->quantite_manquante > 0)
                                    <div class="text-emerald-600 font-semibold">{{ $rechargeLigne->quantite_recue }} reçu(s)</div>
                                    <div class="text-rose-600 font-bold text-xs bg-rose-100 px-2 py-0.5 rounded-full inline-block mt-1 border border-rose-200">
                                        Dette fournisseur: {{ $rechargeLigne->quantite_manquante }} manquant(s)
                                    </div>
                                @else
                                    <span class="text-emerald-600 font-bold text-xs bg-emerald-100 px-2 py-0.5 rounded-full"><i class="ri-check-line mr-1"></i>Complet</span>
                                @endif
                            </td>
                        @endif
                        <td class="p-4 text-right text-slate-600">
                            {{ number_format($ligne->prix_unitaire, 0, ',', ' ') }} {{ param("currency") }}
                        </td>
                        <td class="p-4 text-right font-bold text-slate-800">
                            {{ number_format($ligne->prix_unitaire * $ligne->quantite, 0, ',', ' ') }} {{ param("currency") }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-slate-50/50">
                    <td colspan="{{ $hasReceptionIssue ? '4' : '3' }}" class="p-4 text-right font-medium text-slate-600 uppercase text-xs tracking-wider">
                        Montant Total de la facture
                    </td>
                    <td class="p-4 text-right font-bold text-2xl text-slate-800">
                        {{ number_format($achat->montant_total, 0, ',', ' ') }} {{ param("currency") }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
