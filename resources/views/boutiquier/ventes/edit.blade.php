@extends('layouts.boutiquier')

@section('content')
<div class="mb-8">
    <a href="{{ route('boutiquier.ventes.historique') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à l'historique
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Modifier la vente #{{ str_pad($vente->id, 4, '0', STR_PAD_LEFT) }}</h2>
    <p class="text-sm text-slate-500">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
</div>

@if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center">
        <i class="ri-error-warning-line text-lg mr-2"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
        <div class="flex items-center mb-2">
            <i class="ri-error-warning-fill text-lg mr-2"></i>
            <span class="font-bold">Erreur de validation :</span>
        </div>
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-white shadow rounded-2xl p-6">
    <form action="{{ route('boutiquier.ventes.update', $vente) }}" method="POST" data-offline-sync="true">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Produit -->
            <div>
                <label for="produit_id" class="block text-sm font-semibold text-slate-700 mb-2">
                    <i class="ri-inbox-line mr-1"></i> Produit
                </label>
                <select name="produit_id" id="produit_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Sélectionner un produit</option>
                    @foreach($produits as $produit)
                        <option value="{{ $produit->id }}" {{ $vente->lignes->first()->produit_id == $produit->id ? 'selected' : '' }}>
                            {{ $produit->nom }}
                            @if($produit->reference)
                                ({{ $produit->reference }})
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('produit_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Quantité -->
            <div>
                <label for="quantite" class="block text-sm font-semibold text-slate-700 mb-2">
                    <i class="ri-number-1 mr-1"></i> Quantité
                </label>
                <input type="number" name="quantite" id="quantite" value="{{ $vente->lignes->first()->quantite }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" required>
                @error('quantite')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Grossiste -->
            <div>
                <label for="is_grossiste" class="block text-sm font-semibold text-slate-700 mb-2 flex items-center">
                    <input type="checkbox" name="is_grossiste" id="is_grossiste" {{ $vente->lignes->first()->est_grossiste ? 'checked' : '' }} class="rounded border-slate-300 text-blue-500 mr-2">
                    Vente grossiste
                </label>
            </div>

            <!-- Sélectionner un grossiste (si applicable) -->
            <div id="grossiste_container" style="display: {{ $vente->lignes->first()->est_grossiste ? 'block' : 'none' }};">
                <label for="grossiste_id" class="block text-sm font-semibold text-slate-700 mb-2">
                    <i class="ri-building-2-line mr-1"></i> Grossiste
                </label>
                <select name="grossiste_id" id="grossiste_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Sélectionner un grossiste</option>
                    @foreach(\App\Models\Grossiste::all() as $grossiste)
                        <option value="{{ $grossiste->id }}" {{ $vente->grossiste_id == $grossiste->id ? 'selected' : '' }}>
                            {{ $grossiste->nom }}
                        </option>
                    @endforeach
                </select>
                @error('grossiste_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('boutiquier.ventes.historique') }}" class="px-6 py-2 bg-slate-200 text-slate-800 rounded-lg hover:bg-slate-300 transition-colors font-medium">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center">
                <i class="ri-save-line mr-2"></i> Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

<!-- Danger Zone -->
<div class="mt-8 bg-white shadow rounded-2xl p-6 border-l-4 border-red-500">
    <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center">
        <i class="ri-alert-line mr-2"></i> Zone de danger
    </h3>
    <p class="text-slate-600 mb-4">Une fois supprimée, cette vente ne peut pas être restaurée. Le stock sera automatiquement ajouté de retour.</p>

    <form action="{{ route('boutiquier.ventes.destroy', $vente) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous certain de vouloir supprimer cette vente ?');" data-offline-sync="true">
        @csrf
        @method('DELETE')
        <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium flex items-center">
            <i class="ri-delete-bin-line mr-2"></i> Supprimer cette vente
        </button>
    </form>
</div>

<script>
    document.getElementById('is_grossiste').addEventListener('change', function() {
        const container = document.getElementById('grossiste_container');
        container.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
            document.getElementById('grossiste_id').value = '';
        }
    });
</script>
