@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.recharges.validation.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour
    </a>
    <h2 class="text-3xl font-bold text-white mb-2 tracking-tight">Recharge #{{ $recharge->id }}</h2>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Détails de la recharge -->
    <div class="lg:col-span-2">
        <div class="glass-panel rounded-2xl p-8 mb-6">
            <h3 class="text-xl font-bold text-slate-800 mb-4">Informations</h3>
            <div class="space-y-3 text-slate-600">
                <p><strong>Destination :</strong> {{ $recharge->destination?->nom ?? 'N/A' }}</p>
                <p><strong>Fournisseur :</strong> {{ $recharge->fournisseur?->nom ?? '-' }}</p>
                <p><strong>Date :</strong> {{ $recharge->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Statut :</strong>
                    @php
                        $statusLabels = [
                            'en_attente' => ['label' => 'En attente', 'class' => 'bg-yellow-100 text-yellow-800'],
                            'confirmee_par_magasinier' => ['label' => 'Confirmée par magasinier', 'class' => 'bg-blue-100 text-blue-800'],
                            'anomalie' => ['label' => 'Anomalie signalée', 'class' => 'bg-rose-100 text-rose-800'],
                            'approuvee' => ['label' => 'Approuvée', 'class' => 'bg-emerald-100 text-emerald-800'],
                            'approuvee_avec_perte' => ['label' => 'Approuvée avec perte', 'class' => 'bg-rose-100 text-rose-800'],
                            'rejetee' => ['label' => 'Rejetée', 'class' => 'bg-amber-100 text-amber-800'],
                            'confirmee' => ['label' => 'Confirmée', 'class' => 'bg-blue-100 text-blue-800'],
                        ];
                        $status = $statusLabels[$recharge->statut] ?? ['label' => ucfirst(str_replace('_', ' ', $recharge->statut)), 'class' => 'bg-slate-100 text-slate-800'];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                </p>
            </div>
        </div>

        <!-- Détails de confirmation du magasinier -->
        <div class="glass-panel rounded-2xl p-8 mb-6 border-l-4 border-blue-500">
            <h3 class="text-xl font-bold text-slate-800 mb-4">Confirmation du Magasinier</h3>

            @if($recharge->statut === 'confirmee_par_magasinier')
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-check-circle-line text-2xl text-blue-600 mr-3"></i>
                        <div>
                            <p class="font-semibold text-blue-900">Recharge confirmée par le magasinier</p>
                            <p class="text-sm text-blue-700">Les quantités réelles ont été enregistrées.</p>
                        </div>
                    </div>
                </div>
            @elseif($recharge->statut === 'anomalie')
                <div class="mb-4 p-4 bg-rose-50 border border-rose-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="ri-alert-circle-line text-2xl text-rose-600 mr-3 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-rose-900">Problème signalé par le magasinier</p>
                            <p class="text-sm text-rose-700 mt-2">
                                <strong>Message :</strong> {{ $recharge->message_probleme ?? 'Aucun message fourni' }}
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($recharge->statut === 'approuvee' && $recharge->lignes->sum('quantite_manquante') > 0)
                <div class="mb-4 p-4 bg-rose-50 border border-rose-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="ri-hand-coin-line text-2xl text-rose-600 mr-3 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-rose-900">Perte fournisseur reconnue</p>
                            <p class="text-sm text-rose-700 mt-2">
                                Le fournisseur <strong>{{ $recharge->fournisseur?->nom ?? 'N/A' }}</strong> doit à l'entreprise <strong>{{ $recharge->lignes->sum('quantite_manquante') }}</strong> pièce(s) perdues.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @if($recharge->raison_rejet)
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm text-amber-900">
                        <strong>Raison du rejet :</strong> {{ $recharge->raison_rejet }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Produits -->
        <div class="glass-panel rounded-2xl p-8 mb-6">
            <h3 class="text-xl font-bold text-slate-800 mb-4">Produits confirmés</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-100">
                            <th class="px-4 py-3 text-left text-sm font-semibold">Produit</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold">Attendu</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold">Reçu</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold">Manquant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recharge->lignes as $ligne)
                            <tr class="border-b border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3">{{ $ligne->produit?->nom ?? 'Produit supprimé' }}@if($ligne->produit && $ligne->produit->reference) ({{ $ligne->produit->reference }})@endif</td>
                                <td class="px-4 py-3 text-center">{{ $ligne->quantite_envoyee }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ $ligne->quantite_recue }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-2 py-1 @if($ligne->quantite_manquante > 0) bg-rose-100 text-rose-800 @else bg-green-100 text-green-800 @endif rounded text-xs font-semibold">
                                        {{ $ligne->quantite_manquante }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @php
                $anomalieLignes = $recharge->lignes->where('quantite_manquante', '>', 0);
            @endphp
            @if($anomalieLignes->isNotEmpty())
                <div class="mt-4 p-4 bg-rose-50 border border-rose-200 rounded-lg">
                    <p class="font-semibold text-rose-900">Anomalies par produit</p>
                    <p class="text-sm text-rose-700 mt-2">
                        {{ $anomalieLignes->count() }} produit(s) ont une quantité reçue inférieure à la quantité attendue.
                    </p>
                </div>
            @endif
        </div>

        <!-- Justificatifs -->
        @if($recharge->justificatifs->isNotEmpty())
            <div class="glass-panel rounded-2xl p-8">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Justificatifs</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($recharge->justificatifs as $justif)
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            @if(\Illuminate\Support\Str::endsWith($justif->path, ['.jpg', '.jpeg', '.png', '.gif', '.webp']))
                                <img src="{{ asset('storage/' . $justif->path) }}" alt="Justificatif" class="w-full h-48 object-cover">
                            @else
                                <div class="w-full h-48 bg-slate-100 flex items-center justify-center">
                                    <i class="ri-file-line text-4xl text-slate-400"></i>
                                </div>
                            @endif
                            <div class="p-3 bg-slate-50">
                                <p class="text-xs text-slate-500">{{ ucfirst($justif->type) }}</p>
                                <p class="text-xs text-slate-400">{{ $justif->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="lg:col-span-1">
        <div class="sticky top-6 space-y-4">
            <!-- Approuver -->
            <form action="{{ route('admin.recharges.validation.valider', $recharge) }}" method="POST">
                @csrf
                <button type="submit" class="w-full px-6 py-3 bg-linear-to-r from-green-600 to-emerald-600 text-white rounded-lg font-bold shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center justify-center">
                    <i class="ri-check-circle-line mr-2 text-xl"></i> Approuver (OK)
                </button>
            </form>

            @if($recharge->statut !== 'confirmee_par_magasinier')
                <!-- Rejeter -->
                <div x-data="{ showRejectForm: false }" class="space-y-3">
                    <button @click="showRejectForm = !showRejectForm" type="button" class="w-full px-6 py-3 bg-linear-to-r from-rose-600 to-red-600 text-white rounded-lg font-bold shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center justify-center">
                        <i class="ri-close-circle-line mr-2 text-xl"></i> Rejeter
                    </button>

                    <form x-show="showRejectForm" action="{{ route('admin.recharges.validation.rejeter', $recharge) }}" method="POST" class="glass-panel p-4 rounded-lg space-y-3" @submit="showRejectForm = false">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-rose-600 text-white rounded-lg font-medium hover:bg-rose-700 transition-colors">
                            Confirmer rejet
                        </button>
                        <button type="button" @click="showRejectForm = false" class="w-full px-4 py-2 bg-slate-300 text-slate-800 rounded-lg font-medium hover:bg-slate-400 transition-colors">
                            Annuler
                        </button>
                    </form>
                </div>

                <!-- Informations supplémentaires -->
                <div class="glass-panel p-4 rounded-lg mt-6">
                    <h4 class="font-semibold text-slate-700 mb-2">Note</h4>
                    <p class="text-sm text-slate-600">
                        Si la recharge est une anomalie, <strong>Approuver (OK)</strong> enregistre le stock réellement reçu par le magasin et conserve la dette fournisseur égale à <strong>quantité attendue - quantité reçue</strong>. <strong>Rejeter</strong> permet de refuser l’anomalie sans saisir de motif.
                    </p>
                </div>
            @else
                <div class="glass-panel p-4 rounded-lg mt-6">
                    <h4 class="font-semibold text-slate-700 mb-2">Note</h4>
                    <p class="text-sm text-slate-600">
                        Cette recharge a été confirmée par le magasinier. L'administrateur peut seulement consulter les détails et approuver pour enregistrer le stock reçu.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
