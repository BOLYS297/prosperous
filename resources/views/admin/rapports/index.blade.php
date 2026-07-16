@extends('layouts.admin')

@section('content')
<div class="flex justify-between items-center mb-8">
    <div>
        <h2 class="text-3xl font-bold text-slate-800 mb-2 tracking-tight">Rapports et Statistiques</h2>
        <p class="text-black">Vue d'ensemble financière et performances des boutiques.</p>
    </div>

    <div class="flex space-x-3">
        <a href="javascript:window.print()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl shadow-sm transition-colors flex items-center">
            <i class="ri-printer-line mr-2"></i> Imprimer / PDF
        </a>
        <a href="{{ route('admin.rapports.export.csv', ['mois' => $mois, 'annee' => $annee, 'boutique_id' => $boutiqueId]) }}" class="px-4 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 font-bold rounded-xl shadow-sm transition-colors flex items-center">
            <i class="ri-file-excel-2-line mr-2"></i> Exporter Excel
        </a>
    </div>
</div>

<!-- Filtre Période -->
<div class="glass-panel p-6 rounded-2xl mb-8 print:hidden">
    <form action="{{ route('admin.rapports.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-2">Mois</label>
            <select name="mois" class="w-full px-4 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                @for($m=1; $m<=12; $m++)
                    <option value="{{ sprintf('%02d', $m) }}" {{ $mois == sprintf('%02d', $m) ? 'selected' : '' }}>
                        {{ ucfirst(\Carbon\Carbon::create()->month($m)->translatedFormat('F')) }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-2">Année</label>
            <select name="annee" class="w-full px-4 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                @for($y=date('Y')-2; $y<=date('Y'); $y++)
                    <option value="{{ $y }}" {{ $annee == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>

        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-2">Boutique</label>
            <select name="boutique_id" class="w-full px-4 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Toutes les boutiques</option>
                @foreach($boutiques as $boutique)
                    <option value="{{ $boutique->id }}" {{ (string) $boutiqueId === (string) $boutique->id ? 'selected' : '' }}>{{ $boutique->nom }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-colors flex items-center h-[42px]">
            <i class="ri-filter-3-line mr-2"></i> Mettre à jour
        </button>
    </form>
</div>

<!-- Key Metrics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="glass-panel p-6 rounded-2xl border-t-4 border-blue-500">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Ventes Totales</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalVentes, 0, ',', ' ') }} {{ param("currency") }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
                <i class="ri-shopping-cart-2-line text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl border-t-4 border-rose-500">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Dépenses validées</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalDepenses, 0, ',', ' ') }} {{ param("currency") }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600">
                <i class="ri-money-dollar-circle-line text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl border-t-4 border-slate-500">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Dépenses en attente</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalDepensesPending, 0, ',', ' ') }} {{ param("currency") }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <i class="ri-time-line text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl border-t-4 border-amber-500">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Achats (Stock)</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalAchats, 0, ',', ' ') }} {{ param("currency") }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                <i class="ri-truck-line text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl border-t-4 border-purple-500">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Pertes validées (nombre)</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ $totalPertes }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600">
                <i class="ri-delete-bin-2-line text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl border-t-4 border-slate-500">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Pertes en attente (nombre)</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ $totalPertesPending }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-700">
                <i class="ri-time-line text-2xl"></i>
            </div>
        </div>
    </div>

    <div class="glass-panel p-6 rounded-2xl border-t-4 {{ $cashFlow >= 0 ? 'border-emerald-500' : 'border-red-500' }}">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-sm font-medium text-slate-500" title="Ventes moins dépenses et achats. Ce n'est pas le bénéfice : voir le rapport Bénéfices.">Flux de trésorerie</p>
                <h3 class="text-2xl font-black {{ $cashFlow >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1">
                    {{ $cashFlow >= 0 ? '+' : '' }}{{ number_format($cashFlow, 0, ',', ' ') }} {{ param("currency") }}
                </h3>
            </div>
            <div class="w-12 h-12 rounded-xl {{ $cashFlow >= 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' }} flex items-center justify-center">
                <i class="ri-funds-line text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Ventes par boutique -->
    <div class="glass-panel rounded-2xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
            <i class="ri-store-2-line mr-2 text-blue-500"></i> Performances par Boutique
        </h3>
        <div class="space-y-4">
            @forelse($ventesParBoutique as $boutique)
                @php
                    $pourcentage = $totalVentes > 0 ? round(($boutique->ventes_sum_montant_total / $totalVentes) * 100) : 0;
                @endphp
                <div>
                    <div class="flex justify-between text-sm font-bold text-slate-700 mb-1">
                        <span>{{ $boutique->nom }}</span>
                        <span>{{ number_format($boutique->ventes_sum_montant_total, 0, ',', ' ') }} {{ param("currency") }}</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $pourcentage }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-slate-500 text-sm">Aucune donnée disponible pour cette période.</p>
            @endforelse
        </div>
    </div>

    <!-- Évolution des 6 derniers mois -->
    <div class="glass-panel rounded-2xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center">
            <i class="ri-bar-chart-line mr-2 text-indigo-500"></i> Évolution Mensuelle
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500">
                        <th class="py-2 font-semibold">Mois</th>
                        <th class="py-2 font-semibold text-right text-blue-600">Ventes</th>
                        <th class="py-2 font-semibold text-right text-rose-600">Dépenses</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_reverse($statsMensuelles) as $stat)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                            <td class="py-3 font-medium text-slate-700">{{ $stat['mois'] }}</td>
                            <td class="py-3 text-right font-bold text-slate-800">{{ number_format($stat['ventes'], 0, ',', ' ') }}</td>
                            <td class="py-3 text-right font-medium text-slate-500">{{ number_format($stat['depenses'], 0, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="glass-panel rounded-2xl p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800 flex items-center"><i class="ri-box-fill mr-2 text-emerald-500"></i> Stock total et capital par Boutique</h3>
            <p class="text-sm text-slate-500">Valeur du stock au prix d'achat, par boutique.</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-slate-500">
                    <th class="py-3 font-semibold">Boutique</th>
                    <th class="py-3 font-semibold text-right">Produits différents</th>
                    <th class="py-3 font-semibold text-right">Quantité en stock</th>
                    <th class="py-3 font-semibold text-right">Capital stock</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventesParBoutique as $boutique)
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                        <td class="py-3 font-medium text-slate-700">{{ $boutique->nom }}</td>
                        <td class="py-3 text-right font-bold text-slate-800">{{ number_format($boutique->stock_total_produits ?? 0, 0, ',', ' ') }}</td>
                        <td class="py-3 text-right font-bold text-slate-800">{{ number_format($boutique->stock_total_quantite ?? 0, 0, ',', ' ') }}</td>
                        <td class="py-3 text-right font-bold text-slate-800">{{ number_format($boutique->stock_total_capital ?? 0, 0, ',', ' ') }} {{ param("currency") }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-4 text-center text-slate-500">Aucune boutique ou stock disponible.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel rounded-2xl p-6 mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800 flex items-center"><i class="ri-calendar-todo-line mr-2 text-amber-500"></i> Ventes journalières par boutique</h3>
            <p class="text-sm text-slate-500">Synthèse quotidienne des ventes enregistrées pour chaque boutique sur la période sélectionnée.</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-slate-200 text-slate-500">
                    <th class="py-3 font-semibold">Jour</th>
                    <th class="py-3 font-semibold">Boutique</th>
                    <th class="py-3 font-semibold text-right">Montant total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventesJournalieresParBoutique as $jour => $ventesDuJour)
                    @foreach($ventesDuJour as $venteJournaliere)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                            <td class="py-3 font-medium text-slate-700">{{ \Carbon\Carbon::parse($jour)->translatedFormat('d/m/Y') }}</td>
                            <td class="py-3 font-medium text-slate-700">{{ $venteJournaliere->boutique ? $venteJournaliere->boutique->nom : 'Boutique inconnue' }}</td>
                            <td class="py-3 text-right font-bold text-slate-800">{{ number_format($venteJournaliere->total_ventes, 0, ',', ' ') }} {{ param("currency") }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="3" class="py-4 text-center text-slate-500">Aucune vente enregistrée pour cette période.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
    <div class="glass-panel rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800 flex items-center"><i class="ri-wallet-line mr-2 text-emerald-500"></i> Dépenses en attente</h3>
                <p class="text-sm text-slate-500">Validez ou rejetez les demandes de dépenses des boutiques.</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">{{ $pendingDepenses->count() }} en attente</span>
        </div>

        @if($pendingDepenses->isEmpty())
            <p class="text-slate-500">Aucune dépense en attente pour cette période.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">
                            <th class="py-3 font-semibold">Boutique</th>
                            <th class="py-3 font-semibold">Montant</th>
                            <th class="py-3 font-semibold">Créée par</th>
                            <th class="py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                        @foreach($pendingDepenses as $depense)
                            <tbody x-data="{ open: false }" class="border-b border-slate-100 hover:bg-slate-50/50 align-top">
                                <tr>
                                    <td class="py-3 font-medium text-slate-700">{{ $depense->boutique->nom ?? 'Magasinier' }}</td>
                                    <td class="py-3 font-bold text-slate-800">{{ number_format($depense->montant, 0, ',', ' ') }} {{ param("currency") }}</td>
                                    <td class="py-3 text-slate-500">{{ $depense->user->nom_utilisateur ?? '—' }}</td>
                                    <td class="py-3">
                                        <div class="flex flex-col gap-2">
                                            <button type="button" @click="open = !open" class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">Voir détails</button>
                                            <form action="{{ route('admin.rapports.depenses.approve', $depense) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="w-full px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold">Valider</button>
                                            </form>
                                        <form action="{{ route('admin.rapports.depenses.reject', $depense) }}" method="POST" class="inline">
                                            @csrf
                                                <button type="submit" class="w-full px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">Rejeter</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr x-show="open" x-cloak class="bg-slate-50">
                                    <td colspan="4" class="px-4 py-4 text-sm text-slate-700">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <p class="font-semibold text-slate-700">Intitulé</p>
                                                <p>{{ $depense->intitule ?? 'Dépense administrative' }}</p>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-700">Montant</p>
                                                <p>{{ number_format($depense->montant, 0, ',', ' ') }} {{ param("currency") }}</p>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-700">Description</p>
                                                <p class="text-slate-600">{{ $depense->description ?? 'Aucune description' }}</p>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-700">Créée par</p>
                                                <p>{{ $depense->user->nom_utilisateur ?? '—' }} le {{ $depense->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                        @if($depense->photo_justificatif)
                                            <div class="mt-4">
                                                <p class="font-semibold text-slate-700">Justificatif</p>
                                                <div class="mt-2 rounded-2xl overflow-hidden border border-slate-200 bg-white">
                                                    <img src="{{ asset('storage/'.$depense->photo_justificatif) }}" alt="Justificatif dépense" class="w-full object-contain max-h-72">
                                                </div>
                                                <a href="{{ asset('storage/'.$depense->photo_justificatif) }}" target="_blank" class="inline-flex items-center gap-2 text-blue-700 hover:underline mt-3 block">
                                                    <i class="ri-file-image-line"></i> Ouvrir la pièce jointe
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        @endforeach
                </table>
            </div>
        @endif
    </div>

    <div class="glass-panel rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-slate-800 flex items-center"><i class="ri-delete-bin-2-line mr-2 text-purple-500"></i> Pertes en attente</h3>
                <p class="text-sm text-slate-500">Revoyez les pertes signalées avant de valider la sortie de stock.</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold">{{ $pendingPertes->count() }} en attente</span>
        </div>

        @if($pendingPertes->isEmpty())
            <p class="text-slate-500">Aucune perte en attente pour cette période.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500">
                            <th class="py-3 font-semibold">Boutique / Produit</th>
                            <th class="py-3 font-semibold">Quantité</th>
                            <th class="py-3 font-semibold">Créée par</th>
                            <th class="py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                        @foreach($pendingPertes as $perte)
                            <tbody x-data="{ open: false }" class="border-b border-slate-100 hover:bg-slate-50/50 align-top">
                                <tr>
                                    <td class="py-3 font-medium text-slate-700">{{ $perte->boutique->nom ?? 'Boutique inconnue' }} / {{ $perte->produit->nom ?? 'Produit inconnu' }}</td>
                                    @php $dispo = $stockDisponiblePertes[$perte->id] ?? 0; @endphp
                                    <td class="py-3 font-bold text-slate-800">
                                        {{ $perte->quantite }}
                                        @if($dispo < $perte->quantite)
                                            <span class="block mt-1 inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-[10px] font-bold" title="La validation sera refusée tant que le stock ne couvre pas la perte">
                                                <i class="ri-alert-line mr-1"></i> {{ $dispo }} en stock
                                            </span>
                                        @else
                                            <span class="block text-[11px] font-normal text-slate-400">{{ $dispo }} en stock</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-slate-500">{{ $perte->user->nom_utilisateur ?? '—' }}</td>
                                    <td class="py-3">
                                        <div class="flex flex-col gap-2">
                                            <button type="button" @click="open = !open" class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-sm font-semibold">Voir détails</button>
                                            <form action="{{ route('admin.rapports.pertes.approve', $perte) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="w-full px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold">Valider</button>
                                            </form>
                                        <form action="{{ route('admin.rapports.pertes.reject', $perte) }}" method="POST" class="inline">
                                            @csrf
                                                <button type="submit" class="w-full px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-semibold">Rejeter</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr x-show="open" x-cloak class="bg-slate-50">
                                    <td colspan="4" class="px-4 py-4 text-sm text-slate-700">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <p class="font-semibold text-slate-700">Produit</p>
                                                <p>{{ $perte->produit->nom ?? 'Non spécifié' }}</p>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-700">Quantité</p>
                                                <p>{{ $perte->quantite }}</p>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-700">Raison</p>
                                                <p class="text-slate-600">{{ $perte->raison }}</p>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-700">Créée par</p>
                                                <p>{{ $perte->user->nom_utilisateur ?? '—' }} le {{ $perte->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                        @if($perte->photo_justificatif)
                                            <div class="mt-4">
                                                <p class="font-semibold text-slate-700">Justificatif</p>
                                                <div class="mt-2 rounded-2xl overflow-hidden border border-slate-200 bg-white">
                                                    <img src="{{ asset('storage/'.$perte->photo_justificatif) }}" alt="Justificatif perte" class="w-full object-contain max-h-72">
                                                </div>
                                                <a href="{{ asset('storage/'.$perte->photo_justificatif) }}" target="_blank" class="inline-flex items-center gap-2 text-blue-700 hover:underline mt-3 block">
                                                    <i class="ri-file-image-line"></i> Ouvrir la pièce jointe
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>
        @endif
    </div>
</div>

<style>
    @media print {
        body { background: white; }
        .glass-panel { box-shadow: none !important; border: 1px solid #e2e8f0; }
        aside, header, .print\:hidden { display: none !important; }
        main { padding: 0 !important; margin: 0 !important; }
    }
</style>
@endsection
