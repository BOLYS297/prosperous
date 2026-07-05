@extends('layouts.magasinier')

@section('content')
<div class="mb-8 flex justify-between items-end">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Mon Stock Local</h2>
        <p class="text-black">Consultez la quantité de produits disponibles dans votre boutique.</p>
    </div>
</div>

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 bg-white/50 border-b border-slate-200/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h3 class="font-bold text-slate-800">Inventaire</h3>
        <form action="{{ route('magasinier.stocks.index') }}" method="GET" class="w-full md:w-1/2">
            <label for="q" class="sr-only">Rechercher produit</label>
            <div class="relative">
                <input id="q" name="q" type="text" value="{{ old('q', $q ?? '') }}" placeholder="Rechercher un produit dans le stock..." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pl-10 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                <i class="ri-search-line absolute left-3 top-3 text-slate-400"></i>
            </div>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold w-16">Image</th>
                    <th class="p-4 font-semibold">Nom du Produit</th>
                    <th class="p-4 font-semibold">Référence</th>
                    <th class="p-4 font-semibold text-center">Quantité en Stock</th>
                    <th class="p-4 font-semibold text-center">État</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($produits as $produit)
                    @php
                        // Somme des quantités sur tous les lots pour la boutique courante
                        $quantite = $produit->stocks->sum('quantite');
                    @endphp
                    <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4">
                            @if($produit->image)
                                <img src="{{ asset('storage/' . $produit->image) }}" class="h-10 w-10 object-cover rounded-lg shadow-sm border border-white/50" alt="{{ $produit->nom }}">
                            @else
                                <div class="h-10 w-10 bg-slate-200 rounded-lg flex items-center justify-center text-slate-400 border border-white/50 shadow-sm">
                                    <i class="ri-image-line"></i>
                                </div>
                            @endif
                        </td>
                        <td class="p-4 font-bold text-slate-800 text-lg">
                            {{ $produit->nom }}
                        </td>
                        <td class="p-4 text-center text-slate-600">
                            @if($produit->reference)
                                <span class="text-xs font-mono bg-slate-100 px-2 py-1 rounded">{{ $produit->reference }}</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="p-4 text-center font-black text-xl text-slate-700">
                            {{ $quantite }}
                        </td>
                        <td class="p-4 text-center">
                            @if($quantite > 10)
                                <span class="px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-700">En stock</span>
                            @elseif($quantite > 0)
                                <span class="px-3 py-1 rounded-full text-sm font-bold bg-amber-100 text-amber-700">Stock faible</span>
                            @else
                                <span class="px-3 py-1 rounded-full text-sm font-bold bg-rose-100 text-rose-700">Rupture</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-500">
                            Aucun produit n'est enregistré dans la base de données de l'entreprise.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
