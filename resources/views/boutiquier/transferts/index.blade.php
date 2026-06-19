@extends('layouts.boutiquier')

@section('content')
<div x-data>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Demandes de Stock</h2>
            <p class="text-black">Suivez vos demandes d'approvisionnement depuis le magasin.</p>
        </div>
        <a href="{{ route('boutiquier.transferts.create') }}" class="px-6 py-3 bg-white text-blue-600 font-bold rounded-xl shadow-lg hover:bg-blue-50 transition-colors flex items-center">
            <i class="ri-add-line mr-2"></i> Nouvelle Demande
        </a>
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
                    <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                        <th class="p-4 font-semibold">Date</th>
                        <th class="p-4 font-semibold">Produit</th>
                        <th class="p-4 font-semibold text-center">Qté Demandée</th>
                        <th class="p-4 font-semibold text-center">Qté Expédiée</th>
                        <th class="p-4 font-semibold">Statut</th>
                        <th class="p-4 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($demandes as $demande)
                        <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                            <td class="p-4 text-slate-500">
                                {{ $demande->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-4 font-bold text-slate-800">
                                {{ $demande->produit->nom ?? '—' }}
                                @if($demande->produit && $demande->produit->reference)
                                    <div class="text-xs text-slate-500 font-mono mt-1">{{ $demande->produit->reference }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-center font-bold text-slate-700">
                                {{ $demande->quantite_demandee }}
                            </td>
                            <td class="p-4 text-center font-bold text-blue-600">
                                {{ $demande->quantite_expediee ?? '-' }}
                            </td>
                            <td class="p-4">
                                @if($demande->statut == 'en_attente')
                                    <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold border border-slate-200">En attente</span>
                                @elseif($demande->statut == 'expediee')
                                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-600 text-xs font-bold border border-blue-200 animate-pulse"><i class="ri-truck-line mr-1"></i> En transit</span>
                                @elseif($demande->statut == 'livree')
                                    <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-600 text-xs font-bold border border-emerald-200">Livrée</span>
                                @elseif($demande->statut == 'probleme')
                                    <span class="px-3 py-1 rounded-full bg-rose-100 text-rose-600 text-xs font-bold border border-rose-200" title="{{ $demande->note_probleme }}">Problème signalé</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                @if($demande->statut == 'expediee')
                                    <div class="flex items-center justify-end space-x-2">
                                        <form action="{{ route('boutiquier.transferts.confirmer', $demande->id) }}" method="POST" data-offline-sync="true">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 bg-blue-700 hover:bg-blue-600 text-white rounded-lg text-xs font-bold transition-colors shadow-sm" onclick="return confirm('Confirmez-vous avoir reçu la totalité des produits ?')">
                                                <i class="ri-check-double-line"></i> Confirmer
                                            </button>
                                        </form>

                                        <button type="button" @click="$dispatch('open-probleme', { id: {{ $demande->id }} })" class="px-3 py-1.5 bg-blue-700 hover:bg-blue-600 text-white rounded-lg text-xs font-bold transition-colors shadow-sm">
                                            <i class="ri-error-warning-line"></i> Problème
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
                                Aucune demande de stock pour le moment.
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
    <!-- Modal Problème centralisé -->
    <div x-data="{ showProbleme: false, demandeId: null }" @open-probleme.window="showProbleme = true; demandeId = $event.detail.id">
        <div x-show="showProbleme" style="display: none; z-index: 9999999999; position: fixed; top:0; left:0; width:100%; height:100%;" class="fixed inset-0 bg-slate-900/60 flex items-center justify-center p-4" @click.self="showProbleme = false">

            <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl text-left relative" @click.stop style="position: relative; top: 50%; transform: translateY(-50%); mx-auto">
                <!-- Close button -->
                <button type="button" @click="showProbleme = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="ri-close-line text-2xl"></i>
                </button>

                <h3 class="text-xl font-bold text-rose-600 mb-4 flex items-center"><i class="ri-error-warning-line mr-2"></i> Signaler un problème</h3>
                <p class="text-slate-600 text-sm mb-4">Décrivez le problème rencontré (ex: manque 2 produits, produit abîmé, etc.).</p>

                <form :action="'{{ url('boutiquier/transferts') }}/' + demandeId + '/probleme'" method="POST" data-offline-sync="true">
                    @csrf
                    <div class="grid gap-4">
                        <label class="block text-sm font-semibold text-slate-700">
                            Quantité réellement reçue
                            <input type="number" name="quantite_recue" min="0" class="w-full mt-2 p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 outline-none" required placeholder="Entrez la quantité reçue">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">
                            Message au magasinier
                            <textarea name="note_probleme" rows="3" class="w-full mt-2 p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-rose-500 outline-none" required placeholder="Votre message pour le magasinier..."></textarea>
                        </label>
                    </div>

                    <div class="flex justify-end space-x-3 mt-4">
                        <button type="button" @click="showProbleme = false" class="px-4 py-2 text-slate-500 hover:text-slate-700 font-medium">Annuler</button>
                        <button type="submit" class="px-4 py-2 bg-blue-700 text-white rounded-xl font-bold hover:bg-blue-600 shadow-md">Envoyer le signalement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
