@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.achats.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à l'historique
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Modifier l'achat #{{ str_pad($achat->id, 4, '0', STR_PAD_LEFT) }}</h2>
</div>

<div class="glass-panel rounded-2xl p-8">
    <form action="{{ route('admin.achats.update', $achat) }}" method="POST">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 pb-8 border-b border-slate-200/50">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Fournisseur <span class="text-red-500">*</span></label>
                <select name="fournisseur_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="">-- Sélectionner --</option>
                    @foreach($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}" {{ $achat->fournisseur_id == $fournisseur->id ? 'selected' : '' }}>{{ $fournisseur->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Magasin de destination <span class="text-red-500">*</span></label>
                <select name="boutique_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="">-- Sélectionner un magasin --</option>
                    @foreach($magasins as $boutique)
                        <option value="{{ $boutique->id }}" {{ $achat->boutique_id == $boutique->id ? 'selected' : '' }}>{{ $boutique->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Boutique concernée <span class="text-slate-400 text-xs font-normal">(débit au comptant, ou responsable de la dette — vide = partagée)</span></label>
                <select name="debit_boutique_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">— Partagée / aucune —</option>
                    @foreach($allBoutiques as $boutique)
                        <option value="{{ $boutique->id }}" {{ $achat->debit_boutique_id == $boutique->id ? 'selected' : '' }}>{{ $boutique->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Statut du paiement <span class="text-red-500">*</span></label>
                <select name="statut" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="paye" {{ $achat->statut === 'paye' ? 'selected' : '' }}>Payé comptant (déduit du solde)</option>
                    <option value="dette" {{ $achat->statut === 'dette' ? 'selected' : '' }}>Achat à crédit (dette)</option>
                </select>
            </div>
        </div>

        <div class="space-y-4 mb-8">
            @foreach($achat->lignes as $index => $ligne)
                <div class="flex items-end gap-4 p-4 bg-white/40 border border-slate-200 rounded-xl">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Produit</label>
                        <select name="lignes[{{ $index }}][produit_id]" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                            @foreach($produits as $produit)
                                <option value="{{ $produit->id }}" {{ $ligne->produit_id == $produit->id ? 'selected' : '' }}>{{ $produit->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Prix Unitaire</label>
                        <input type="number" step="0.01" name="lignes[{{ $index }}][prix_unitaire]" value="{{ $ligne->prix_unitaire }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Prix de Vente</label>
                        <input type="number" step="0.01" name="lignes[{{ $index }}][prix_vente]" value="{{ old('lignes.'.$index.'.prix_vente', $ligne->prix_vente ?? $ligne->produit->prix_vente ?? '') }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Prix Grossiste</label>
                        <input type="number" step="0.01" name="lignes[{{ $index }}][prix_vente_grossiste]" value="{{ old('lignes.'.$index.'.prix_vente_grossiste', $ligne->prix_vente_grossiste ?? '') }}" placeholder="Optionnel" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="w-24">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Qté</label>
                        <input type="number" min="1" name="lignes[{{ $index }}][quantite]" value="{{ $ligne->quantite }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.achats.index') }}" class="px-6 py-3 bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300 transition-colors font-medium">Annuler</a>
            <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-semibold">Enregistrer les modifications</button>
        </div>
    </form>
</div>
@endsection
