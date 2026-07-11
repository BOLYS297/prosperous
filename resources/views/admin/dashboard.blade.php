@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Vue d'ensemble</h2>
    <p class="text-black">Statistiques financières et activités récentes.</p>
    <div class="mt-4">
        <a href="{{ route('admin.depenses.create') }}" class="inline-flex items-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">
            <i class="ri-add-line mr-2"></i> Créer une dépense personnelle
        </a>
    </div>
</div>

@if(!empty($rechargesEnAttente) && $rechargesEnAttente > 0)
    <div class="mb-8 glass-panel rounded-2xl p-6 bg-gradient-to-r from-amber-50 to-orange-50 border border-orange-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mr-4">
                    <i class="ri-alert-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">{{ $rechargesEnAttente }} recharge(s) en attente de validation</h3>
                    <p class="text-sm text-slate-600">Les magasiniers ont confirmé des recharges qui nécessitent votre approbation.</p>
                </div>
            </div>
            <a href="{{ route('admin.recharges.validation.index') }}" class="px-6 py-2 bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all">
                Valider maintenant
            </a>
        </div>
    </div>
@endif

@if(!empty($rechargesAnomalies) && $rechargesAnomalies > 0)
    <div class="mb-8 glass-panel rounded-2xl p-6 bg-gradient-to-r from-rose-50 to-pink-50 border border-rose-200">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mr-4">
                    <i class="ri-bug-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">{{ $rechargesAnomalies }} problème(s) de recharge signalés</h3>
                    <p class="text-sm text-slate-600">Les magasins ont déclaré des anomalies. Vérifiez et traitez-les rapidement.</p>
                </div>
            </div>
            <a href="{{ route('admin.recharges.validation.index') }}" class="px-6 py-2 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-lg font-semibold hover:shadow-lg transition-all">
                Voir les anomalies
            </a>
        </div>
    </div>
@endif

@if(session('status'))
    <div class="mb-8 p-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700">
        {{ session('status') }}
    </div>
@endif

<div class="mb-8 glass-panel rounded-2xl p-6 border border-slate-200">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Déduction salaire en cas de retard</h3>
            <p class="text-sm text-slate-500">Définissez le montant à déduire par heure de retard sur le salaire des employés.</p>
        </div>
        <span class="inline-flex items-center px-3 py-2 rounded-full bg-slate-100 text-slate-700 text-sm font-medium">
            Actuellement : <strong class="ml-2">{{ number_format($deductionHourlyAmount, 0, ',', ' ') }} FCFA / heure</strong>
        </span>
    </div>

    <form action="{{ route('admin.dashboard.deduction.update') }}" method="POST" class="grid gap-4 md:grid-cols-2 items-end">
        @csrf
        <div class="md:col-span-1">
            <label class="block text-sm font-medium text-slate-700 mb-2" for="hourly_retard_amount">Montant par heure de retard (FCFA)</label>
            <input id="hourly_retard_amount" name="hourly_retard_amount" type="number" min="0" value="{{ old('hourly_retard_amount', $deductionHourlyAmount) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none" required>
            @error('hourly_retard_amount')
                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-1">
            <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-2xl hover:bg-blue-700 transition-colors">Enregistrer</button>
        </div>
    </form>
</div>

<!-- Section Déductions en attente -->
<div class="mb-8 glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-slate-200 bg-slate-50">
        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <i class="ri-alert-line text-2xl text-amber-600"></i>
                <h3 class="text-xl font-bold text-slate-800">Déductions en attente d'approbation</h3>
                <span class="ml-auto px-3 py-1 bg-amber-100 text-amber-700 text-sm font-bold rounded-full">{{ $pendingDeductions->count() }}</span>
            </div>
            <form action="{{ route('admin.dashboard') }}" method="GET" class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                <div class="relative flex-1 min-w-0">
                    <label for="deduction_search" class="sr-only">Rechercher déduction</label>
                    <input id="deduction_search" name="deduction_search" type="text" value="{{ $deductionSearch }}" autocomplete="off" data-instant-search="#pending-deductions-body, #approved-deductions-body" placeholder="Rechercher par employé, type ou montant..." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pl-10 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                    <i class="ri-search-line absolute left-3 top-3 text-slate-400"></i>
                </div>
                <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-2xl hover:bg-blue-700 transition-colors">Filtrer</button>
                @if(!empty($deductionSearch))
                    <a href="{{ route('admin.dashboard') }}" class="px-5 py-3 border border-slate-300 rounded-2xl text-slate-700 hover:bg-slate-100 transition-colors">Effacer</a>
                @endif
            </form>
        </div>
    </div>

    @if($pendingDeductions->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                        <th class="p-4 text-left font-semibold">Employé</th>
                        <th class="p-4 text-left font-semibold">Type</th>
                        <th class="p-4 text-left font-semibold">Heure événement</th>
                        <th class="p-4 text-left font-semibold">Retard</th>
                        <th class="p-4 text-left font-semibold">Montant (FCFA)</th>
                        <th class="p-4 text-center font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody id="pending-deductions-body">
                    @foreach($pendingDeductions as $deduction)
                        <tr data-search="{{ \Illuminate\Support\Str::lower(($deduction->user?->nom_utilisateur ?? '') . ' ' . ($deduction->user?->role ?? '') . ' ' . $deduction->event_type_label . ' ' . $deduction->amount) }}" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                            <td class="p-4">
                                <div class="font-bold text-slate-800">{{ $deduction->user?->nom_utilisateur ?? 'Utilisateur supprimé' }}</div>
                                <div class="text-xs text-slate-500">{{ ucfirst($deduction->user?->role ?? 'inconnu') }}</div>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $deduction->event_type_label }}</span>
                            </td>
                            <td class="p-4 text-slate-700">{{ $deduction->actual_event_at?->format('d/m/Y H:i:s') ?? 'N/A' }}</td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-700 text-sm font-semibold">
                                    {{ intval($deduction->minutes_late / 60) }}h{{ $deduction->minutes_late % 60 }}min
                                </span>
                            </td>
                            <td class="p-4">
                                <span class="text-lg font-bold text-rose-600">{{ number_format($deduction->amount, 0, ',', ' ') }}</span>
                            </td>
                            <td class="p-4 flex items-center justify-center gap-2">
                                <form action="{{ route('admin.deductions.approve', $deduction) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition-colors" onclick="return confirm('Approuver cette déduction ?')">
                                        <i class="ri-check-line"></i> OK
                                    </button>
                                </form>
                                <form action="{{ route('admin.deductions.reject', $deduction) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 bg-rose-600 text-white text-xs font-semibold rounded-lg hover:bg-rose-700 transition-colors" onclick="return confirm('Rejeter cette déduction ?')">
                                        <i class="ri-close-line"></i> Rejeter
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-6 bg-white border-t border-slate-200 text-slate-700">
            {{-- <p class="text-sm">Aucune déduction en attente ne correspond à cette recherche.</p> --}}
            @if(!empty($deductionSearch))
                <p class="mt-2 text-sm text-slate-500">Effacez le filtre pour revenir à toutes les déductions en attente.</p>
            @else
                <p class="mt-2 text-sm text-slate-500">Aucune déduction en attente n'est disponible pour le moment.</p>
            @endif
        </div>
    @endif
</div>

<!-- Section Historique Déductions Approuvées -->
@if($approvedDeductions && $approvedDeductions->count() > 0)
<div class="mb-8 glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-slate-200 bg-emerald-50">
        <div class="flex items-center gap-2">
            <i class="ri-check-circle-line text-2xl text-emerald-600"></i>
            <h3 class="text-xl font-bold text-slate-800">Historique des déductions approuvées</h3>
            <span class="ml-auto px-3 py-1 bg-emerald-100 text-emerald-700 text-sm font-bold rounded-full">{{ $approvedDeductions->count() }}</span>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                    <th class="p-4 text-left font-semibold">Employé</th>
                    <th class="p-4 text-left font-semibold">Type</th>
                    <th class="p-4 text-left font-semibold">Heure événement</th>
                    <th class="p-4 text-left font-semibold">Retard</th>
                    <th class="p-4 text-left font-semibold">Montant (FCFA)</th>
                    <th class="p-4 text-left font-semibold">Approuvé par</th>
                    <th class="p-4 text-left font-semibold">Date approbation</th>
                    <th class="p-4 text-center font-semibold">Action</th>
                </tr>
            </thead>
            <tbody id="approved-deductions-body">
                @foreach($approvedDeductions as $deduction)
                    <tr data-search="{{ \Illuminate\Support\Str::lower(($deduction->user?->nom_utilisateur ?? '') . ' ' . ($deduction->user?->role ?? '') . ' ' . $deduction->event_type_label . ' ' . $deduction->amount) }}" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                        <td class="p-4">
                            <div class="font-bold text-slate-800">{{ $deduction->user->nom_utilisateur }}</div>
                            <div class="text-xs text-slate-500">{{ ucfirst($deduction->user->role) }}</div>
                        </td>
                        <td class="p-4">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $deduction->event_type_label }}</span>
                        </td>
                        <td class="p-4 text-slate-700">{{ $deduction->actual_event_at?->format('d/m/Y H:i:s') ?? 'N/A' }}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-semibold">
                                {{ intval($deduction->minutes_late / 60) }}h{{ $deduction->minutes_late % 60 }}min
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="text-lg font-bold text-slate-700">{{ number_format($deduction->amount, 0, ',', ' ') }}</span>
                        </td>
                        <td class="p-4">
                            <span class="text-slate-700">{{ $deduction->approver ? $deduction->approver->nom_utilisateur : 'Admin système' }}</span>
                        </td>
                        <td class="p-4">
                            <span class="text-slate-600">{{ $deduction->approved_at ? $deduction->approved_at->format('d/m/Y H:i') : 'N/A' }}</span>
                        </td>
                        <td class="p-4 text-center">
                            <form action="{{ route('admin.deductions.reject', $deduction) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="px-3 py-2 bg-rose-600 text-white text-xs font-semibold rounded-lg hover:bg-rose-700 transition-colors" onclick="return confirm('Annuler cette déduction déjà validée ? Le salaire de l\'employé sera recalculé.')">
                                    <i class="ri-arrow-go-back-line"></i> Annuler
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="mb-8 glass-panel rounded-2xl p-6 border border-slate-200 bg-slate-50">
    <div class="flex items-center gap-3 text-slate-600">
        <i class="ri-check-circle-line text-2xl text-slate-400"></i>
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Aucune déduction approuvée</h3>
            <p class="text-sm text-slate-500">L'historique des déductions approuvées s'affichera ici.</p>
        </div>
    </div>
</div>
@endif

<div class="mb-8 glass-panel rounded-2xl p-6 border border-slate-200">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Produits les plus vendus</h3>
            <p class="text-sm text-slate-500">Consultez les articles qui ont été le plus vendus en quantité.</p>
        </div>
        <form action="{{ route('admin.dashboard.deduction.update') }}" method="POST" class="flex flex-col sm:flex-row sm:items-center gap-3">
            @csrf
            <div>
                <label for="top_products_count" class="block text-sm font-medium text-slate-700 mb-2">Top des produits</label>
                <input id="top_products_count" name="top_products_count" type="number" min="1" max="20" value="{{ old('top_products_count', $topProductsCount ?? 5) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none" required>
                @error('top_products_count')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="h-14 px-6 py-3 bg-blue-600 text-white font-semibold rounded-2xl hover:bg-blue-700 transition-colors">Afficher</button>
        </form>
    </div>
    <div class="mb-4">
        <span class="inline-flex items-center px-3 py-2 rounded-full bg-slate-100 text-slate-700 text-sm font-medium">
            Top {{ $topProductsCount ?? 5 }} produits
        </span>
    </div>

    @if($topProducts && $topProducts->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                        <th class="p-4 text-left font-semibold">Produit</th>
                        <th class="p-4 text-right font-semibold">Quantité vendue</th>
                        <th class="p-4 text-right font-semibold">CA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $product)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                            <td class="p-4 text-slate-800">
                                {{ $product->produit_nom ?? 'Produit inconnu' }}@if($product->produit_reference) ({{ $product->produit_reference }})@endif
                            </td>
                            <td class="p-4 text-right font-semibold text-slate-900">
                                {{ number_format($product->total_quantity, 0, ',', ' ') }}
                            </td>
                            <td class="p-4 text-right font-semibold text-slate-700">
                                {{ number_format($product->total_revenue, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-sm text-slate-500">Aucun produit vendu n'a encore été enregistré.</div>
    @endif
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Card 1: Solde Entreprise -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ri-wallet-3-fill text-6xl text-blue-600"></i>
        </div>
        <h3 class="text-slate-500 font-medium text-sm mb-1 uppercase tracking-wider">Solde Total Entreprise</h3>
        <p class="text-3xl font-bold text-slate-800">{{ number_format($entrepriseSolde, 0, ',', ' ') }} <span class="text-xl text-slate-500 font-normal">FCFA</span></p>
        <div class="mt-4 flex items-center text-sm text-slate-500 font-medium">
            <i class="ri-arrow-up-line mr-1"></i> Total disponible sur toutes les boutiques
        </div>
    </div>

    <!-- Card 2: Bénéfice Net -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ri-pie-chart-2-fill text-6xl text-emerald-600"></i>
        </div>
        <h3 class="text-slate-500 font-medium text-sm mb-1 uppercase tracking-wider">Bénéfice Net</h3>
        <p class="text-3xl font-bold text-slate-800">{{ $profitNet >= 0 ? '+' : '' }}{{ number_format($profitNet, 0, ',', ' ') }} <span class="text-xl text-slate-500 font-normal">FCFA</span></p>
        <div class="mt-4 flex items-center text-sm {{ $profitNet >= 0 ? 'text-emerald-500' : 'text-rose-500' }} font-medium">
            <i class="ri-arrow-{{ $profitNet >= 0 ? 'up' : 'down' }}-line mr-1"></i>
            {{ $profitNet >= 0 ? 'Profit estimé' : 'Perte estimée' }}
        </div>
    </div>

    <!-- Card 3: Boutique 1 -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ri-store-2-fill text-6xl text-indigo-600"></i>
        </div>
        <h3 class="text-slate-500 font-medium text-sm mb-1 uppercase tracking-wider">{{ $topBoutique ? $topBoutique->nom : 'Boutique principale' }}</h3>
        <p class="text-3xl font-bold text-slate-800">{{ $topBoutique ? number_format($topBoutique->solde, 0, ',', ' ') : '0' }} <span class="text-xl text-slate-500 font-normal">FCFA</span></p>
        <div class="mt-4 flex items-center text-sm text-slate-500 font-medium">
            <i class="ri-arrow-up-line mr-1"></i> Meilleure situation financière
        </div>
    </div>

    <!-- Card 3: Boutique 2 -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ri-store-3-fill text-6xl text-purple-600"></i>
        </div>
        <h3 class="text-slate-500 font-medium text-sm mb-1 uppercase tracking-wider">{{ $secondBoutique ? $secondBoutique->nom : 'Autre boutique' }}</h3>
        <p class="text-3xl font-bold text-slate-800">{{ $secondBoutique ? number_format($secondBoutique->solde, 0, ',', ' ') : '0' }} <span class="text-xl text-slate-500 font-normal">FCFA</span></p>
        <div class="mt-4 flex items-center text-sm text-slate-500 font-medium">
            <i class="ri-arrow-up-line mr-1"></i> Deuxième meilleure boutique
        </div>
    </div>

    <!-- Card 4: Dettes Fournisseurs -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ri-hand-coin-fill text-6xl text-rose-600"></i>
        </div>
        <div class="flex items-center gap-2 mb-1">
            <h3 class="text-slate-500 font-medium text-sm uppercase tracking-wider">Dettes Fournisseurs</h3>
            @if(!empty($rechargesAnomalies) && $rechargesAnomalies > 0)
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-100 px-2 py-1 rounded-full">
                    <i class="ri-alert-fill"></i> {{ $rechargesAnomalies }} anomalie(s)
                </span>
            @endif
        </div>
        <p class="text-3xl font-bold text-rose-600">{{ number_format($dettesFournisseurs, 0, ',', ' ') }} <span class="text-xl font-normal">FCFA</span></p>
        <div class="mt-4 flex items-center text-sm text-slate-500 font-medium">
            <i class="ri-time-line mr-1"></i> Montant restant à régler
        </div>
    </div>

    <!-- Card 5: Achats & Stock -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ri-truck-line text-6xl text-indigo-600"></i>
        </div>
        <div class="flex items-center gap-2 mb-1">
            <h3 class="text-slate-500 font-medium text-sm uppercase tracking-wider">Achats & Stock</h3>
            @if(!empty($rechargesAnomalies) && $rechargesAnomalies > 0)
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-100 px-2 py-1 rounded-full">
                    <i class="ri-alert-fill"></i> {{ $rechargesAnomalies }} anomalie(s)
                </span>
            @endif
        </div>
        <p class="text-3xl font-bold text-slate-800">{{ $rechargesAnomalies > 0 ? $rechargesAnomalies . ' anomalies' : 'Aucun problème' }}</p>
        <div class="mt-4 flex items-center text-sm text-slate-500 font-medium">
            <i class="ri-stack-line mr-1"></i> Gestion du stock et des achats
        </div>
        <a href="{{ route('admin.recharges.validation.index') }}" class="mt-4 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
            Consulter les réceptions
        </a>
    </div>

    <!-- Card 6: Tranches Horaires -->
    <div class="glass-panel rounded-2xl p-6 relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ri-time-line text-6xl text-green-600"></i>
        </div>
        <div class="flex items-center gap-2 mb-1">
            <h3 class="text-slate-500 font-medium text-sm uppercase tracking-wider">Tranches Horaires</h3>
        </div>
        <p class="text-3xl font-bold text-slate-800">Configuration</p>
        <div class="mt-4 flex items-center text-sm text-slate-500 font-medium">
            <i class="ri-settings-3-line mr-1"></i> Heures de connexion des employés
        </div>
        <a href="{{ route('admin.horaires.index') }}" class="mt-4 inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
            Gérer les tranches
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Graphique (Placeholder) -->
    <div class="lg:col-span-2 glass-panel rounded-2xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-slate-800">Évolution des ventes</h3>
            <select class="bg-white border border-slate-200 text-sm rounded-lg px-3 py-2 text-slate-700 outline-none focus:border-blue-500">
                <option>Cette Semaine</option>
                <option>Ce Mois</option>
            </select>
        </div>
        <div class="h-64 flex items-end space-x-2 w-full pt-4">
            @foreach($weeklySales as $day)
                @php
                    $height = 20 + ($day['total'] / max($maxWeeklySales, 1)) * 80;
                @endphp
                <div class="w-full rounded-t-sm bg-blue-500 relative group hover:bg-blue-600 transition-colors cursor-pointer" style="height: {{ $height }}%;">
                    <span class="absolute -top-6 w-full text-center text-xs font-bold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">{{ $day['label'] }}</span>
                    <span class="absolute bottom-2 left-1/2 -translate-x-1/2 text-xs text-slate-800 font-semibold">{{ number_format($day['total'], 0, ',', ' ') }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Activités récentes -->
    <div class="glass-panel rounded-2xl p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-6">Activités Récentes</h3>
        <div class="space-y-4">
            @forelse($recentActivities as $activity)
                <div class="flex items-start">
                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center shrink-0">
                        <i class="ri-history-line"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-slate-800">{{ $activity->action_label }}</p>
                        <p class="text-xs text-slate-500">{{ $activity->description_label }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <div class="text-sm text-slate-500">Aucune activité récente n'a encore été consignée.</div>
            @endforelse
        </div>
        <button class="w-full mt-6 py-2 text-sm text-blue-600 font-medium border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors"><a href="{{ route('admin.logs.index') }}" class="text-blue-600 hover:text-blue-800" {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}>Voir tout l'historique</a></button>
    </div>
</div>
@endsection
