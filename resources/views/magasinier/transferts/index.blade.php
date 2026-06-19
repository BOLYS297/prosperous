@extends('layouts.magasinier')

@section('content')
<div x-data>
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-slate-800 mb-2 tracking-tight">Demandes d'Approvisionnement</h2>
        <p class="text-black">Gérez les demandes de stock provenant des différentes boutiques.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
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

    <div class="glass-panel rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                        <th class="p-4 font-semibold">Date</th>
                        <th class="p-4 font-semibold">Boutique</th>
                        <th class="p-4 font-semibold">Produit</th>
                        <th class="p-4 font-semibold text-center">Qté Demandée</th>
                        <th class="p-4 font-semibold">Statut</th>
                        <th class="p-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($demandes as $demande)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                            <td class="p-4 text-slate-500">
                                {{ $demande->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-4 font-bold text-slate-800">
                                {{ $demande->boutique->nom ?? '—' }}
                            </td>
                            <td class="p-4 font-bold text-slate-700">
                                {{ $demande->produit->nom ?? '—' }}@if($demande->produit && $demande->produit->reference) ({{ $demande->produit->reference }})@endif
                            </td>
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center justify-center px-3 py-1 rounded-lg bg-blue-50 text-blue-700 font-black text-base border border-blue-100">
                                    {{ $demande->quantite_demandee }}
                                </span>
                            </td>
                            <td class="p-4">
                                @if($demande->statut == 'en_attente')
                                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold border border-amber-200"><i class="ri-time-line mr-1"></i> À traiter</span>
                                @elseif($demande->statut == 'expediee')
                                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-600 text-xs font-bold border border-blue-200">En transit</span>
                                @elseif($demande->statut == 'livree')
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold border border-emerald-200">Livrée</span>
                                @elseif($demande->statut == 'probleme')
                                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-600 text-xs font-bold border border-rose-200" title="{{ $demande->note_probleme }}"><i class="ri-alert-line mr-1"></i> Problème signalé</span>
                                    <div class="text-xs text-rose-500 mt-1 max-w-xs">{{ $demande->note_probleme }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                @if($demande->statut == 'en_attente')
                                    <div class="flex justify-end">
                                        <button type="button" @click="$dispatch('open-expedier', { id: {{ $demande->id }}, bNom: '{{ addslashes($demande->boutique->nom) }}', pNom: '{{ addslashes($demande->produit->nom) }}@if($demande->produit && $demande->produit->reference) ({{ addslashes($demande->produit->reference) }})@endif', qty: {{ $demande->quantite_demandee }} })" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition-colors shadow-md flex items-center">
                                            <i class="ri-truck-line mr-1"></i> Expédier
                                        </button>
                                    </div>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-500">
                                <i class="ri-inbox-line text-4xl block mb-2"></i>
                                Aucune demande d'approvisionnement.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('modals')
    <div x-data="{ showExpedier: false, demandeId: null, boutiqueNom: '', produitNom: '', quantite: 1 }" @open-expedier.window="showExpedier = true; demandeId = $event.detail.id; boutiqueNom = $event.detail.bNom; produitNom = $event.detail.pNom; quantite = $event.detail.qty">
        <div x-show="showExpedier" x-transition.opacity class="fixed inset-0 bg-slate-900/60" style="display: none; z-index: 9999999999; position: fixed; top:0; left:0; width:100%; height:100%;" @click.self="showExpedier = false">
            <div class="bg-white rounded-2xl p-6 w-full max-w-2xl shadow-2xl text-left relative max-h-[calc(100vh-4rem)] overflow-y-auto mx-auto" @click.stop style="position: relative; top: 50%; transform: translateY(-50%);">
                <button type="button" @click="showExpedier = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="ri-close-line text-2xl"></i>
                </button>

                <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center"><i class="ri-truck-line mr-2 text-blue-600"></i> Expédier la commande</h3>

                <div class="mb-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="text-sm text-slate-500">Destination : <strong class="text-slate-800" x-text="boutiqueNom"></strong></div>
                    <div class="text-sm text-slate-500">Produit : <strong class="text-slate-800" x-text="produitNom"></strong></div>
                    <div class="text-sm text-slate-500 mt-2">Quantité demandée : <strong class="text-blue-600 text-lg" x-text="quantite"></strong></div>
                </div>

                <form data-offline-sync="true" :action="'{{ url('magasinier/transferts') }}/' + demandeId + '/expedier'" method="POST">
                    @csrf
                    <label class="block text-sm font-medium text-slate-700 mb-2">Quantité réellement expédiée <span class="text-red-500">*</span></label>
                    <p class="text-xs text-slate-500 mb-2">Vous pouvez ajuster cette quantité si vous n'avez pas assez de stock.</p>
                    <input type="number" name="quantite_expediee" min="1" x-model="quantite" class="w-full px-4 py-3 border border-slate-300 rounded-xl mb-6 bg-white focus:ring-2 focus:ring-blue-500 outline-none text-xl font-black text-slate-800" required>

                    <div class="flex justify-end space-x-3 mt-4">
                        <button type="button" @click="showExpedier = false" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium">Annuler</button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 shadow-md">Confirmer l'envoi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
