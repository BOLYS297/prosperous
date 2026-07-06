@extends('layouts.admin')

@section('content')
<div class="mb-8 flex justify-between items-end">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Catalogue Produits</h2>
        <p class="text-black">Gérez la liste de tous les produits disponibles dans vos boutiques.</p>
    </div>
    <a href="{{ route('admin.produits.create') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center">
        <i class="ri-add-line mr-2"></i> Nouveau Produit
    </a>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
        <i class="ri-checkbox-circle-line text-lg mr-2"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 border-b border-white/40 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <form action="{{ route('admin.produits.index') }}" method="GET" class="flex-1 min-w-0">
            <label for="q" class="sr-only">Recherche produit</label>
            <div class="relative w-full">
                <input id="q" name="q" type="text" value="{{ old('q', $q ?? '') }}" autocomplete="off" data-instant-search="#produits-tbody" data-instant-search-empty="#produits-empty" placeholder="Rechercher un produit..." class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" />
                <i class="ri-search-line absolute left-3 top-3 text-slate-400"></i>
            </div>
        </form>
        <div class="text-sm text-slate-500">
            Total : <span class="font-bold text-slate-800">{{ $produits->count() }}</span> produits
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold w-16">Image</th>
                    <th class="p-4 font-semibold">Nom du Produit</th>
                    <th class="p-4 font-semibold">Référence</th>
                    <th class="p-4 font-semibold">Stock total</th>
                    <th class="p-4 font-semibold">Prix d'Achat</th>
                    <th class="p-4 font-semibold">Prix de Vente</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="produits-tbody" class="text-sm">
                @forelse($produits as $produit)
                    <tr data-search="{{ \Illuminate\Support\Str::lower($produit->nom . ' ' . $produit->reference) }}" class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4">
                            @if($produit->image)
                                <img src="{{ asset('storage/' . $produit->image) }}" class="h-10 w-10 object-cover rounded-lg shadow-sm border border-white/50" alt="{{ $produit->nom }}">
                            @else
                                <div class="h-10 w-10 bg-slate-200 rounded-lg flex items-center justify-center text-slate-400 border border-white/50 shadow-sm">
                                    <i class="ri-image-line"></i>
                                </div>
                            @endif
                        </td>
                        <td class="p-4 font-bold text-slate-800">
                            {{ $produit->nom }}
                        </td>
                        <td class="p-4 text-slate-700 text-sm">
                            @if($produit->reference)
                                <code class="bg-slate-100 px-2 py-1 rounded text-xs">{{ $produit->reference }}</code>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="p-4 text-slate-700">
                            @php
                                $magasinStock = $produit->stocks->where('boutique.type', 'magasin')->sum('quantite');
                                $boutiqueStocks = $produit->stocks->where('boutique.type', 'boutique');
                                $totalStock = $produit->stocks->sum('quantite');
                            @endphp
                            <div class="text-sm font-semibold">{{ $totalStock }} pcs</div>
                            <div class="text-xs text-slate-500 mt-1">
                                Magasin : {{ $magasinStock }}
                            </div>
                            @if($boutiqueStocks->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($boutiqueStocks as $stock)
                                        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-600 text-[11px]">{{ $stock->boutique->nom ?? 'Boutique' }}: {{ $stock->quantite }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">
                                {{ number_format($produit->prix_achat, 0, ',', ' ') }} FCFA
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700">
                                {{ number_format($produit->prix_vente, 0, ',', ' ') }} FCFA
                            </span>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('admin.produits.edit', $produit) }}" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition-colors" title="Modifier">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <form action="{{ route('admin.produits.destroy', $produit) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition-colors" title="Supprimer">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <i class="ri-price-tag-3-line text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-800">Aucun produit</h3>
                            <p class="text-slate-500 mt-1">Commencez par ajouter votre premier produit au catalogue.</p>
                            <a href="{{ route('admin.produits.create') }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Ajouter un produit</a>
                        </td>
                    </tr>
                @endforelse
                <tr id="produits-empty" style="display:none">
                    <td colspan="7" class="p-12 text-center text-slate-500">Aucun produit ne correspond à votre recherche.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
