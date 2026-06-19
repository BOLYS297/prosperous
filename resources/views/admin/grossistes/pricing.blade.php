@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Tarifs - {{ $grossiste->nom }}</h1>
        <a href="{{ route('admin.grossistes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Retour
        </a>
    </div>

    @if ($message = Session::get('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ $message }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.grossistes.pricing.update', $grossiste) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Produit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Prix Achat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Prix Vente</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($produits as $produit)
                        @php
                            $prixExistant = $grossiste->prixProduits->firstWhere('produit_id', $produit->id);
                        @endphp
                        <tr>
                            <td class="px-6 py-4 font-semibold">{{ $produit->nom }}@if($produit->reference) ({{ $produit->reference }})@endif</td>
                            <td class="px-6 py-4">
                                <input type="hidden" name="prix[{{ $loop->index }}][produit_id]" value="{{ $produit->id }}">
                                <input type="number" name="prix[{{ $loop->index }}][prix_achat]" step="0.01" min="0" class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" value="{{ $prixExistant?->prix_achat ?? $produit->prix_achat ?? '' }}" required>
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="prix[{{ $loop->index }}][prix_vente]" step="0.01" min="0" class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" value="{{ $prixExistant?->prix_vente ?? $produit->prix_vente ?? '' }}" required>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Enregistrer les Tarifs
            </button>
            <a href="{{ route('admin.grossistes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Annuler
            </a>
        </div>
    </form>
</div>
@endsection
