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
            <input type="hidden" name="filter" value="{{ $filter }}">
            <label for="q" class="sr-only">Recherche produit</label>
            <div class="relative w-full">
                <input id="q" name="q" type="text" value="{{ old('q', $q ?? '') }}" autocomplete="off" data-instant-search="#produits-tbody" data-instant-search-empty="#produits-empty" placeholder="Rechercher un produit..." class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm" />
                <i class="ri-search-line absolute left-3 top-3 text-slate-400"></i>
            </div>
        </form>
        <div class="text-sm text-slate-500">
            {{ $filter ? 'Affichés' : 'Total' }} : <span class="font-bold text-slate-800">{{ $produits->count() }}</span> produits
        </div>
    </div>

    <div class="px-6 py-4 border-b border-white/40 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-slate-500 mr-1"><i class="ri-filter-3-line mr-1"></i>Filtrer :</span>
        @php
            $filterOptions = [
                '' => ['label' => 'Tous', 'count' => null],
                'incomplets' => ['label' => 'Incomplets', 'count' => $counts['incomplets']],
                'sans_prix_vente' => ['label' => 'Prix de vente manquant', 'count' => $counts['sans_prix_vente']],
                'sans_prix_grossiste' => ['label' => 'Prix grossiste manquant', 'count' => $counts['sans_prix_grossiste']],
                'sans_reference' => ['label' => 'Référence manquante', 'count' => $counts['sans_reference']],
            ];
        @endphp
        @foreach($filterOptions as $key => $opt)
            <a href="{{ route('admin.produits.index', array_filter(['filter' => $key, 'q' => $q], fn ($v) => $v !== '' && $v !== null)) }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors {{ $filter === $key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' }}">
                {{ $opt['label'] }}
                @if($opt['count'] !== null && $opt['count'] > 0)
                    <span class="inline-flex items-center justify-center rounded-full px-1.5 min-w-[1.25rem] {{ $filter === $key ? 'bg-white/25 text-white' : 'bg-rose-100 text-rose-700' }}">{{ $opt['count'] }}</span>
                @endif
            </a>
        @endforeach
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
                    @php
                        $baseVente = $produit->getRawOriginal('prix_vente');
                        $baseGrossiste = $produit->getRawOriginal('prix_vente_grossiste');
                        $missVente = $baseVente === null || (float) $baseVente <= 0;
                        $missGrossiste = $baseGrossiste === null || (float) $baseGrossiste <= 0;
                        $missRef = trim((string) $produit->reference) === '';
                        $rowIncomplet = $missVente || $missGrossiste || $missRef;
                    @endphp
                    <tr data-search="{{ \Illuminate\Support\Str::lower($produit->nom . ' ' . $produit->reference) }}" class="border-b border-white/20 hover:bg-white/30 transition-colors {{ $rowIncomplet ? 'bg-rose-50/60' : '' }}">
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
                            @if($rowIncomplet)
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($missVente)
                                        <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-700 px-2 py-0.5 text-[10px] font-semibold"><i class="ri-error-warning-line mr-1"></i>Prix de vente manquant</span>
                                    @endif
                                    @if($missGrossiste)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2 py-0.5 text-[10px] font-semibold"><i class="ri-error-warning-line mr-1"></i>Prix grossiste manquant</span>
                                    @endif
                                    @if($missRef)
                                        <span class="inline-flex items-center rounded-full bg-slate-200 text-slate-700 px-2 py-0.5 text-[10px] font-semibold"><i class="ri-error-warning-line mr-1"></i>Référence manquante</span>
                                    @endif
                                </div>
                            @endif
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
                                $totalStock = $produit->stocks->sum('quantite');

                                // Le stock est géré en LOTS FIFO : une même boutique en possède
                                // plusieurs pour un même produit. On regroupe par boutique et on
                                // additionne — sinon la boutique apparaît en double — et on masque
                                // celles sans stock (lots vidés par les ventes, qui affichaient « : 0 »).
                                $stockParBoutique = $produit->stocks
                                    ->where('boutique.type', 'boutique')
                                    ->groupBy('boutique_id')
                                    ->map(fn ($lots) => (object) [
                                        'nom' => $lots->first()->boutique->nom ?? 'Boutique',
                                        'quantite' => $lots->sum('quantite'),
                                    ])
                                    ->filter(fn ($b) => $b->quantite > 0)
                                    ->sortBy('nom')
                                    ->values();
                            @endphp
                            <div class="text-sm font-semibold">{{ $totalStock }} pcs</div>
                            <div class="text-xs text-slate-500 mt-1">
                                Magasin : {{ $magasinStock }}
                            </div>
                            @if($stockParBoutique->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($stockParBoutique as $boutiqueStock)
                                        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-600 text-[11px]">{{ $boutiqueStock->nom }}: {{ $boutiqueStock->quantite }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">
                                {{ number_format($produit->prix_achat, 0, ',', ' ') }} {{ param("currency") }}
                            </span>
                        </td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700">
                                {{ number_format($produit->prix_vente, 0, ',', ' ') }} {{ param("currency") }}
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
