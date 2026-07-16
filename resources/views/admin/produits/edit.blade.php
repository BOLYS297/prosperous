@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.produits.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour au catalogue
    </a>
    <h2 class="text-3xl font-bold text-white mb-2 tracking-tight">
        {{ isset($produit) ? 'Modifier le Produit' : 'Ajouter un Produit' }}
    </h2>
</div>

<div class="glass-panel rounded-2xl p-8 max-w-3xl">
    <form action="{{ isset($produit) ? route('admin.produits.update', $produit) : route('admin.produits.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($produit))
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Image du produit (Optionnel)</label>
                <input type="file" name="image" accept="image/*" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                @if(isset($produit) && $produit->image)
                    <div class="mt-2 text-sm text-slate-500 flex items-center">
                        <i class="ri-image-line mr-1"></i> Image actuelle : <img src="{{ asset('storage/' . $produit->image) }}" class="h-10 w-10 object-cover rounded ml-2 shadow-sm border border-slate-200">
                    </div>
                @endif
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Nom du produit <span class="text-red-500">*</span></label>
                <input type="text" name="nom" value="{{ old('nom', $produit->nom ?? '') }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: Ciment CPJ 35" required>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Référence du produit (SKU/Code) <span class="text-slate-500 text-xs font-normal">(Optionnel)</span></label>
                <input type="text" name="reference" value="{{ old('reference', $produit->reference ?? '') }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: SKU-00123456">
                <p class="text-xs text-slate-500 mt-1"><i class="ri-information-line mr-1"></i> La référence doit être unique et sera utilisée pour identifier le produit dans tous les rapports</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Prix d'Achat ({{ param("currency") }}) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="number" step="0.01" name="prix_achat" value="{{ old('prix_achat', $produit->prix_achat ?? '') }}" class="w-full pl-4 pr-16 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: 4000" required>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500 font-medium">
                        {{ param("currency") }}
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Prix de Vente ({{ param("currency") }}) <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="number" step="0.01" name="prix_vente" value="{{ old('prix_vente', $produit->prix_vente ?? '') }}" class="w-full pl-4 pr-16 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: 5000" required>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500 font-medium">
                        {{ param("currency") }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-slate-700 mb-2">Prix de vente grossiste par défaut ({{ param("currency") }})</label>
            <div class="relative max-w-xs">
                <input type="number" step="0.01" min="0" name="prix_vente_grossiste" value="{{ old('prix_vente_grossiste', $produit->prix_vente_grossiste ?? '') }}" class="w-full pl-4 pr-16 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Optionnel">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500 font-medium">
                    {{ param("currency") }}
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                Prix grossiste par défaut du produit, appliqué à toutes les ventes grossistes.
                Les <strong>tarifs spécifiques à chaque grossiste</strong> se définissent dans <strong>Grossistes → Tarifs</strong>.
                Laissez vide pour utiliser le prix de vente client.
            </p>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-slate-700 mb-2">Prix de vente hors heures ({{ param("currency") }})</label>
            <div class="relative max-w-xs">
                <input type="number" step="0.01" min="0" name="prix_vente_hors_heures" value="{{ old('prix_vente_hors_heures', $produit->prix_vente_hors_heures ?? '') }}" class="w-full pl-4 pr-16 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Optionnel">
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500 font-medium">
                    {{ param("currency") }}
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                Prix appliqué <strong>en dehors des heures d'ouverture</strong> ({{ \App\Support\TarifHoraire::plageOuverture() }}).
                La différence avec le prix de vente revient à l'employé qui réalise la vente.
                Laissez vide pour appliquer la majoration par défaut ({{ param('majoration_hors_heures_percent', 0) }} %) définie dans <strong>Paramètres</strong>.
            </p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg transform hover:-translate-y-0.5">
                {{ isset($produit) ? 'Mettre à jour le produit' : 'Enregistrer le produit' }}
            </button>
        </div>
    </form>
</div>
@endsection
