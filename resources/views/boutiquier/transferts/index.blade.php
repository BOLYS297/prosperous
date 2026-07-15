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

    {{-- Transferts entre points de vente : ma boutique est la SOURCE --}}
    @if(!empty($aAutoriser) && $aAutoriser->isNotEmpty())
        <div class="mb-6 glass-panel rounded-2xl p-6 bg-amber-50 border border-amber-200">
            <div class="flex items-center gap-2 mb-4">
                <i class="ri-logout-box-r-line text-2xl text-amber-600"></i>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Transferts à autoriser (sortie de stock)</h3>
                    <p class="text-sm text-slate-600">Le magasin demande d'envoyer du stock de votre boutique vers un autre point de vente. Indiquez la quantité que vous autorisez.</p>
                </div>
                <span class="ml-auto inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-3 py-1 text-xs font-semibold shrink-0">{{ $aAutoriser->count() }}</span>
            </div>

            <div class="space-y-4">
                @foreach($aAutoriser as $t)
                    @php $dispo = (int) ($stockDispo[$t->produit_id] ?? 0); @endphp
                    <div class="p-4 rounded-2xl bg-white border border-amber-100">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900">{{ $t->produit?->nom ?? 'Produit' }}@if($t->produit?->reference) <span class="text-xs text-slate-400 font-mono">({{ $t->produit->reference }})</span>@endif</div>
                                <div class="text-sm text-slate-600 mt-1">
                                    Demandé : <strong>{{ $t->quantite_demandee }}</strong> · Destination : <strong>{{ $t->destination?->nom ?? '—' }}</strong>
                                </div>
                                <div class="text-xs mt-1 {{ $dispo <= 0 ? 'text-rose-600 font-semibold' : 'text-slate-500' }}">
                                    Votre stock disponible : <strong>{{ $dispo }}</strong>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                                <form action="{{ route('boutiquier.transferts-stock.autoriser', $t) }}" method="POST" data-offline-sync="true" class="flex items-end gap-2">
                                    @csrf
                                    <div>
                                        <label class="block text-xs text-slate-500 mb-1">Quantité autorisée</label>
                                        <input type="number" name="quantite_autorisee" min="1" max="{{ min($t->quantite_demandee, $dispo) }}" value="{{ min($t->quantite_demandee, $dispo) }}" required class="w-32 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 outline-none">
                                    </div>
                                    <button type="submit" {{ $dispo <= 0 ? 'disabled' : '' }} class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="ri-check-line mr-1"></i> Autoriser
                                    </button>
                                </form>

                                <form action="{{ route('boutiquier.transferts-stock.refuser', $t) }}" method="POST" data-offline-sync="true" class="flex items-end" onsubmit="return confirm('Refuser ce transfert ?');">
                                    @csrf
                                    <input type="hidden" name="note" value="Refusé par la boutique source">
                                    <button type="submit" class="px-4 py-2 bg-slate-200 text-slate-800 text-sm font-semibold rounded-lg hover:bg-slate-300 transition-colors">
                                        <i class="ri-close-line mr-1"></i> Refuser
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Transferts entre points de vente : ma boutique est la DESTINATION --}}
    @if(!empty($aReceptionner) && $aReceptionner->isNotEmpty())
        <div class="mb-6 glass-panel rounded-2xl p-6 bg-blue-50 border border-blue-200">
            <div class="flex items-center gap-2 mb-4">
                <i class="ri-login-box-line text-2xl text-blue-600"></i>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Transferts à réceptionner (entrée de stock)</h3>
                    <p class="text-sm text-slate-600">Confirmez la quantité réellement reçue. Le stock ne sera ajouté qu'après votre confirmation.</p>
                </div>
                <span class="ml-auto inline-flex items-center rounded-full bg-blue-100 text-blue-700 px-3 py-1 text-xs font-semibold shrink-0">{{ $aReceptionner->count() }}</span>
            </div>

            <div class="space-y-4">
                @foreach($aReceptionner as $t)
                    <div class="p-4 rounded-2xl bg-white border border-blue-100">
                        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900">{{ $t->produit?->nom ?? 'Produit' }}@if($t->produit?->reference) <span class="text-xs text-slate-400 font-mono">({{ $t->produit->reference }})</span>@endif</div>
                                <div class="text-sm text-slate-600 mt-1">
                                    Envoyé : <strong>{{ $t->quantite_autorisee }}</strong> · Provenance : <strong>{{ $t->source?->nom ?? '—' }}</strong>
                                </div>
                                <div class="text-xs text-slate-400 mt-1">Expédié {{ $t->authorized_at?->diffForHumans() }}</div>
                            </div>

                            <form action="{{ route('boutiquier.transferts-stock.receptionner', $t) }}" method="POST" data-offline-sync="true" class="flex items-end gap-2 shrink-0">
                                @csrf
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Quantité reçue</label>
                                    <input type="number" name="quantite_recue" min="0" max="{{ $t->quantite_autorisee }}" value="{{ $t->quantite_autorisee }}" required class="w-32 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="ri-inbox-archive-line mr-1"></i> Confirmer la réception
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
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
                                @elseif($demande->statut == 'refusee')
                                    <span class="px-3 py-1 rounded-full bg-slate-200 text-slate-700 text-xs font-bold border border-slate-300" title="{{ $demande->note_probleme }}"><i class="ri-close-circle-line mr-1"></i> Refusée</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                @if($demande->statut == 'en_attente')
                                    {{-- Tant que le magasin n'a pas traité la demande, elle reste modifiable. --}}
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="{{ route('boutiquier.transferts.edit', $demande->id) }}" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition-colors" title="Modifier">
                                            <i class="ri-pencil-line"></i>
                                        </a>
                                        <form action="{{ route('boutiquier.transferts.destroy', $demande->id) }}" method="POST" data-offline-sync="true" onsubmit="return confirm('Supprimer cette demande de stock ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition-colors" title="Supprimer">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($demande->statut == 'expediee')
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
