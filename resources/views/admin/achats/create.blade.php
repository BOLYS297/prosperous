@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.achats.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à l'historique
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Enregistrer un Achat</h2>
</div>

<div class="glass-panel rounded-2xl p-8" x-data="achatForm()" @mount="init()">
    <form action="{{ route('admin.achats.store') }}" method="POST">
        @csrf

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
                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Magasin de destination <span class="text-red-500">*</span></label>
                <select name="boutique_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="">-- Sélectionner un magasin --</option>
                    @foreach($magasins as $boutique)
                        <option value="{{ $boutique->id }}">{{ $boutique->nom }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-2">La destination finale de cet achat est toujours un magasin.</p>
            </div>
            <div x-show="source === 'boutique'">
                <label class="block text-sm font-medium text-slate-700 mb-2" x-text="statut === 'paye' ? 'Boutique à débiter * (comptant)' : 'Dette à la charge de'"></label>
                <select name="debit_boutique_id" id="debit_boutique_select" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="" x-text="statut === 'dette' ? '— Partagée (toutes les boutiques) —' : '-- Sélectionner une boutique --'"></option>
                    @foreach($allBoutiques as $boutique)
                        <option value="{{ $boutique->id }}">{{ $boutique->nom }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-2" x-text="statut === 'paye' ? 'Le solde de cette boutique règle l\'achat comptant.' : 'Choisissez la boutique responsable de la dette, ou laissez « Partagée » pour que toutes les boutiques puissent la régler.'"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Statut du paiement <span class="text-red-500">*</span></label>
                <select name="statut" x-model="statut" @change="toggleDebitField()" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="paye">Payé comptant (déduit du solde)</option>
                    <option value="dette">Achat à crédit (dette)</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Imputer à <span class="text-red-500">*</span></label>
                <div class="flex flex-col sm:flex-row gap-4 text-sm text-slate-700">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="source_paiement" value="boutique" x-model="source" @change="toggleDebitField()" class="text-blue-600 focus:ring-blue-500">
                        <span>Une <strong>boutique</strong></span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="source_paiement" value="solde_admin" x-model="source" @change="toggleDebitField()" class="text-emerald-600 focus:ring-emerald-500">
                        <span>Mon <strong>solde personnel</strong>
                            <span class="text-slate-400">({{ number_format(auth()->user()->solde_personnel ?? 0, 0, ',', ' ') }} {{ param("currency") }} disponible)</span>
                        </span>
                    </label>
                </div>
                <p class="text-xs text-emerald-700 mt-2" x-show="source === 'solde_admin'" x-cloak>
                    <i class="ri-information-line"></i>
                    <span x-text="statut === 'paye' ? 'Le montant sera débité immédiatement de votre solde personnel (aucune validation boutiquier).' : 'La dette sera imputée à votre solde personnel : vous la rembourserez depuis « Mon solde ».'"></span>
                </p>
            </div>
        </div>

        <div class="mb-6 flex justify-between items-end">
            <h3 class="text-lg font-bold text-slate-800">Lignes d'achat (Produits)</h3>
            <button type="button" @click="addLine()" class="px-4 py-2 bg-slate-100 text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-colors text-sm">
                <i class="ri-add-line"></i> Ajouter une ligne
            </button>
        </div>

        <!-- Les achats administrateur sont automatiquement créés comme recharges destinées aux magasins. -->

        <div class="space-y-4 mb-8">
            <template x-for="(ligne, index) in lignes" :key="index">
                <div class="flex items-end gap-4 p-4 bg-white/40 border border-slate-200 rounded-xl transition-all">
                    <div class="flex-1 relative">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Produit</label>
                        <input
                            type="text"
                            :id="`produit_search_${index}`"
                            placeholder="Rechercher un produit..."
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                            autocomplete="off"
                            @input.debounce.300="searchProduits($event.target.value, index)"
                            @keydown.arrow-down="selectNextProduit(index)"
                            @keydown.arrow-up="selectPrevProduit(index)"
                            @keydown.enter="selectCurrentProduit(index)"
                            @keydown.escape="closeProduitSuggestions(index)"
                            required
                        >
                        <input
                            type="hidden"
                            :name="`lignes[${index}][produit_id]`"
                            x-model="ligne.produit_id"
                            @change="updatePrice(index)"
                        >

                        <div
                            :id="`produit_suggestions_${index}`"
                            x-show="ligne.showSuggestions && ligne.suggestions.length > 0"
                            @click.outside="ligne.showSuggestions = false"
                            class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-300 rounded-lg shadow-lg z-50 max-h-48 overflow-y-auto"
                        >
                            <template x-for="(item, itemIndex) in ligne.suggestions" :key="item.id">
                                <div
                                    :class="{ 'bg-blue-100': ligne.selectedIndex === itemIndex, 'hover:bg-slate-50': ligne.selectedIndex !== itemIndex }"
                                    @click="selectProduitItem(item, index)"
                                    @mouseenter="ligne.selectedIndex = itemIndex"
                                    class="px-3 py-2 cursor-pointer border-b border-slate-100 last:border-b-0 text-xs text-slate-800"
                                >
                                    <div class="font-medium" x-text="item.nom"></div>
                                    <div class="text-xs text-slate-500 font-mono" x-show="item.reference" x-text="item.reference"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Prix Unitaire</label>
                        <input type="number" step="0.01" :name="`lignes[${index}][prix_unitaire]`" x-model="ligne.prix_unitaire" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Prix de Vente</label>
                        <input type="number" step="0.01" :name="`lignes[${index}][prix_vente]`" x-model="ligne.prix_vente" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Prix Grossiste</label>
                        <input type="number" step="0.01" :name="`lignes[${index}][prix_vente_grossiste]`" x-model="ligne.prix_vente_grossiste" placeholder="Optionnel" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1" title="Prix appliqué quand la vente a lieu en heures supplémentaires. Vide = prix normal.">Prix H. Supp</label>
                        <input type="number" step="0.01" min="0" :name="`lignes[${index}][prix_vente_hors_heures]`" x-model="ligne.prix_vente_hors_heures" placeholder="Optionnel" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="w-24">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Qté</label>
                        <input type="number" min="1" :name="`lignes[${index}][quantite]`" x-model="ligne.quantite" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Total</label>
                        <div class="px-3 py-2 bg-slate-100 rounded-lg text-sm font-bold text-slate-700 text-right" x-text="(ligne.prix_unitaire * ligne.quantite).toLocaleString() + ' F'"></div>
                    </div>
                    <div>
                        <button type="button" @click="removeLine(index)" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors" x-show="lignes.length > 1">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex justify-between items-center bg-slate-800 text-white p-6 rounded-xl mb-8 shadow-lg">
            <span class="text-lg font-medium text-slate-300">Montant Total de la facture</span>
            <span class="text-3xl font-bold" x-text="calculateTotal().toLocaleString() + ' {{ param("currency") }}'"></span>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-xl transform hover:-translate-y-1 text-lg flex items-center">
                <i class="ri-save-3-line mr-2"></i> Valider l'achat
            </button>
        </div>
    </form>
</div>

<script>
    function achatForm() {
        return {
            produits: @json($produits),
            statut: 'paye',
            source: 'boutique',
            lignes: [
                { produit_id: '', prix_unitaire: 0, prix_vente: 0, prix_vente_grossiste: '', prix_vente_hors_heures: '', quantite: 1, showSuggestions: false, suggestions: [], selectedIndex: -1, searchValue: '' }
            ],
            init() {
                // Initialiser la visibilité du champ debit_boutique_id au chargement
                this.toggleDebitField();
            },
            toggleDebitField() {
                const debitSelect = document.getElementById('debit_boutique_select');
                if (! debitSelect) return;
                // Boutique requise seulement pour un achat comptant imputé à une boutique.
                if (this.statut === 'paye' && this.source === 'boutique') {
                    debitSelect.setAttribute('required', 'required');
                } else {
                    debitSelect.removeAttribute('required');
                }
            },
            addLine() {
                this.lignes.push({ produit_id: '', prix_unitaire: 0, prix_vente: 0, prix_vente_grossiste: '', prix_vente_hors_heures: '', quantite: 1, showSuggestions: false, suggestions: [], selectedIndex: -1, searchValue: '' });
            },
            removeLine(index) {
                if(this.lignes.length > 1) {
                    this.lignes.splice(index, 1);
                }
            },
            updatePrice(index) {
                const produitId = parseInt(this.lignes[index].produit_id);
                const produit = this.produits.find(p => p.id === produitId);
                if(produit) {
                    this.lignes[index].prix_unitaire = produit.prix_achat;
                    this.lignes[index].prix_vente = produit.prix_vente ?? produit.prix_achat;
                    this.lignes[index].prix_vente_hors_heures = produit.prix_vente_hors_heures ?? '';
                }
            },
            searchProduits(query, index) {
                const ligne = this.lignes[index];
                ligne.searchValue = query;

                if (!query || query.length < 1) {
                    ligne.suggestions = [];
                    ligne.showSuggestions = false;
                    return;
                }

                const queryLower = query.toLowerCase();
                ligne.suggestions = this.produits.filter(p =>
                    p.nom.toLowerCase().includes(queryLower) ||
                    (p.reference && p.reference.toLowerCase().includes(queryLower))
                ).map(p => ({
                    id: p.id,
                    nom: p.nom,
                    reference: p.reference,
                    prix_achat: p.prix_achat,
                    prix_vente: p.prix_vente,
                    prix_vente_hors_heures: p.prix_vente_hors_heures
                })).slice(0, 20);

                ligne.showSuggestions = ligne.suggestions.length > 0;
                ligne.selectedIndex = -1;
            },
            selectNextProduit(index) {
                const ligne = this.lignes[index];
                if (ligne.showSuggestions) {
                    ligne.selectedIndex = Math.min(ligne.selectedIndex + 1, ligne.suggestions.length - 1);
                }
            },
            selectPrevProduit(index) {
                const ligne = this.lignes[index];
                if (ligne.showSuggestions) {
                    ligne.selectedIndex = Math.max(ligne.selectedIndex - 1, 0);
                }
            },
            selectCurrentProduit(index) {
                const ligne = this.lignes[index];
                if (ligne.selectedIndex >= 0 && ligne.suggestions[ligne.selectedIndex]) {
                    this.selectProduitItem(ligne.suggestions[ligne.selectedIndex], index);
                }
            },
            selectProduitItem(item, index) {
                const ligne = this.lignes[index];
                const input = document.getElementById(`produit_search_${index}`);
                if (input) {
                    input.value = item.nom + (item.reference ? ` (${item.reference})` : '');
                }
                ligne.produit_id = item.id;
                ligne.prix_unitaire = item.prix_achat;
                ligne.prix_vente = item.prix_vente ?? item.prix_achat;
                ligne.prix_vente_hors_heures = item.prix_vente_hors_heures ?? '';
                ligne.showSuggestions = false;
                ligne.suggestions = [];
                ligne.searchValue = '';
            },
            closeProduitSuggestions(index) {
                this.lignes[index].showSuggestions = false;
            },
            calculateTotal() {
                return this.lignes.reduce((total, ligne) => {
                    return total + (parseFloat(ligne.prix_unitaire || 0) * parseInt(ligne.quantite || 0));
                }, 0);
            }
        }
    }
</script>
@endsection
