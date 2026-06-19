@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Validation des Recharges</h2>
    <p class="text-black">Approuvez ou rejetez les recharges confirmées par les magasiniers.</p>
</div>

@if($recharges->isEmpty())
    <div class="glass-panel rounded-2xl p-8 text-center">
        <i class="ri-inbox-line text-6xl text-slate-300 mb-4"></i>
        <p class="text-slate-600 text-lg">Aucune recharge en attente de validation.</p>
    </div>
@else
    <div class="grid grid-cols-1 gap-4">
        @foreach($recharges as $recharge)
            <div class="glass-panel rounded-2xl p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Recharge #{{ $recharge->id }}</h3>
                        <p class="text-sm text-slate-500">
                            <i class="ri-calendar-line mr-1"></i>{{ $recharge->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    @if($recharge->statut === 'anomalie')
                        <span class="px-3 py-1 bg-rose-100 text-rose-800 rounded-full text-sm font-semibold">⚠️ Anomalie signalée</span>
                    @else
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">En attente de validation</span>
                    @endif
                </div>

                @if($recharge->statut === 'anomalie' && $recharge->message_probleme)
                    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-sm">
                        <p class="text-rose-700"><strong>Problème signalé :</strong> {{ $recharge->message_probleme }}</p>
                    </div>
                @endif

                <div class="mb-4 pb-4 border-b border-slate-200">
                    <p class="text-sm text-slate-600 mb-2">
                        <strong>Destination :</strong> {{ $recharge->destination?->nom ?? 'N/A' }}
                    </p>
                    @if($recharge->fournisseur)
                        <p class="text-sm text-slate-600">
                            <strong>Fournisseur :</strong> {{ $recharge->fournisseur->nom }}
                        </p>
                    @endif
                </div>

                <div class="mb-4">
                    <h4 class="font-semibold text-slate-700 mb-3">Produits confirmés par le magasinier :</h4>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="px-3 py-2 text-left">Produit</th>
                                <th class="px-3 py-2 text-center">Attendu</th>
                                <th class="px-3 py-2 text-center">Reçu</th>
                                <th class="px-3 py-2 text-center">Manquant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recharge->lignes as $ligne)
                                <tr class="border-b border-slate-200 hover:bg-slate-50">
                                    <td class="px-3 py-2">{{ $ligne->produit?->nom ?? 'Produit supprimé' }}@if($ligne->produit && $ligne->produit->reference) ({{ $ligne->produit->reference }})@endif</td>
                                    <td class="px-3 py-2 text-center">{{ $ligne->quantite_envoyee }}</td>
                                    <td class="px-3 py-2 text-center font-semibold">{{ $ligne->quantite_recue }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="inline-block px-2 py-1 @if($ligne->quantite_manquante > 0) bg-rose-100 text-rose-800 @else bg-green-100 text-green-800 @endif rounded text-xs font-semibold">
                                            {{ $ligne->quantite_manquante }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('admin.recharges.validation.show', $recharge) }}" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors text-center">
                        <i class="ri-eye-line mr-2"></i> Voir détails
                    </a>
                    <form action="{{ route('admin.recharges.validation.valider', $recharge) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-black rounded-lg font-medium hover:bg-green-700 transition-colors">
                            <i class="ri-check-line mr-2"></i> Approuver (OK)
                        </button>
                    </form>
                    @if($recharge->statut === 'anomalie')
                        <a href="{{ route('admin.recharges.validation.show', $recharge) }}" class="flex-1 px-4 py-2 bg-rose-600 text-black rounded-lg font-medium hover:bg-rose-700 transition-colors text-center">
                            <i class="ri-close-line mr-2"></i> Rejeter
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $recharges->links() }}
    </div>
@endif
@endsection
