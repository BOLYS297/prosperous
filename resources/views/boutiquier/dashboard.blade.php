@extends('layouts.boutiquier')

@section('content')
@php
    $horsHeures = \App\Support\TarifHoraire::estMajore(auth()->user());
@endphp
<div class="space-y-8">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Point de Vente</h2>
        <p class="text-black">Boutique : <span class="font-bold">{{ $boutique ? $boutique->nom : 'Aucune boutique assignée' }}</span> — {{ now()->translatedFormat('l d F Y') }}</p>
    </div>

    @if($horsHeures)
        <div class="mb-6 rounded-2xl bg-indigo-600 p-5 shadow-lg text-white">
            <div class="flex items-start gap-3">
                <i class="ri-moon-clear-line text-3xl mt-0.5"></i>
                <div>
                    <h3 class="font-bold text-lg">Heures supplémentaires actives</h3>
                    <p class="text-sm text-indigo-100 mt-1">
                        Vous êtes <strong>en dehors de votre session principale</strong> : les prix affichés sont
                        <strong>majorés</strong> pour les ventes client.
                    </p>
                    <p class="text-sm font-semibold text-white mt-1">
                        <i class="ri-hand-coin-line mr-1"></i> La majoration vous revient : elle est cumulée et vous sera payée en fin de mois.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(isset($shiftWarning) && $shiftWarning)
        <div class="mb-6 rounded-2xl bg-amber-100 border border-amber-200 p-5 shadow-sm text-amber-900">
            <div class="flex items-start gap-3">
                <div class="mt-0.5">
                    <i class="ri-time-line text-3xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">Fin de tranche horaire imminente</h3>
                    <p class="text-sm text-amber-700 mt-1">Votre tranche se termine à <strong>{{ $shiftWarning['end'] }}</strong>. Il reste <strong>{{ $shiftWarning['minutes'] }} min {{ $shiftWarning['seconds'] }} s</strong>.</p>
                    <p class="text-xs text-amber-700 mt-1">Sauvegardez vos actions et préparez-vous à clôturer votre session.</p>
                </div>
            </div>
        </div>
    @endif

    @if(isset($pendingValidations) && $pendingValidations->isNotEmpty())
        <div class="mb-6 glass-panel rounded-2xl p-6 bg-rose-50 border border-rose-200">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <i class="ri-bank-card-line text-2xl text-rose-600"></i>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Débits à valider</h3>
                        <p class="text-sm text-slate-600">Confirmez ou contestez ces débits avant qu'ils n'impactent votre solde.</p>
                    </div>
                </div>
                <span class="inline-flex items-center rounded-full bg-rose-100 text-rose-700 px-3 py-1 text-xs font-semibold">{{ $pendingValidations->count() }} en attente</span>
            </div>
            <div class="space-y-4">
                @foreach($pendingValidations as $validation)
                    <div class="p-4 rounded-2xl bg-white border border-rose-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">{{ $validation->source_label }}</span>
                                    <span class="text-lg font-black text-rose-600">{{ number_format($validation->amount, 0, ',', ' ') }} {{ param("currency") }}</span>
                                </div>
                                <p class="text-sm text-slate-700 mt-1">{{ $validation->motif }}</p>
                                <p class="text-xs text-slate-400 mt-1">Par {{ $validation->initiator->nom_utilisateur ?? 'Administrateur' }} · {{ $validation->created_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <form action="{{ route('boutiquier.validations.confirmer', $validation) }}" method="POST" data-offline-sync="true">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Valider ce débit de {{ number_format($validation->amount, 0, ',', ' ') }} {{ param("currency") }} ? Votre solde sera débité.')" class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors flex items-center">
                                        <i class="ri-check-line mr-1"></i> Valider
                                    </button>
                                </form>
                                <form action="{{ route('boutiquier.validations.contester', $validation) }}" method="POST" data-offline-sync="true">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Contester ce débit ? L\'administrateur sera notifié et votre solde ne sera pas débité.')" class="px-4 py-2 bg-slate-200 text-slate-800 text-sm font-semibold rounded-lg hover:bg-slate-300 transition-colors flex items-center">
                                        <i class="ri-close-line mr-1"></i> Contester
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($notifications) && $notifications->isNotEmpty())
        <div class="mb-6 glass-panel rounded-2xl p-6 bg-orange-50 border border-orange-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Notifications importantes</h3>
                    <p class="text-sm text-slate-600">Vous avez des actions à prendre suite à une dépense enregistrée par l'administrateur.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-orange-100 text-orange-700 px-3 py-1 text-xs font-semibold">{{ $notifications->count() }} non lue(s)</span>
            </div>
            <div class="flex items-center justify-between mb-4">
                <div></div>
                <form action="{{ route('boutiquier.notifications.mark_all_read') }}" method="POST" data-offline-sync="true">
                    @csrf
                    <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 font-semibold">Tout marquer comme lu</button>
                </form>
            </div>
            <div class="space-y-4">
                @foreach($notifications as $notification)
                    <div class="p-4 rounded-2xl bg-white border border-orange-100">
                        <p class="text-sm text-slate-700">{{ $notification->data['message'] }}</p>
                        <div class="mt-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500">
                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                            <div class="flex items-center gap-2">
                                @if(!empty($notification->data['action_url']))
                                    <a href="{{ $notification->data['action_url'] }}" class="text-blue-600 hover:text-blue-800 font-bold">Voir</a>
                                @endif
                                <form action="{{ route('boutiquier.notifications.mark_read', $notification->id) }}" method="POST" class="inline" data-offline-sync="true">
                                    @csrf
                                    <button type="submit" class="text-slate-500 hover:text-slate-700">Marquer comme lu</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center animate-pulse">
            <i class="ri-checkbox-circle-line text-lg mr-2"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- <div class="glass-panel p-6 rounded-2xl flex items-center hover:bg-white/80 transition-colors">
            <div class="p-4 bg-blue-100 text-blue-600 rounded-xl mr-4 shadow-sm border border-blue-200">
                <i class="ri-shopping-bag-line text-3xl"></i>
            </div>
            <div>
                <div class="text-sm font-medium text-slate-500 mb-1">Ventes Aujourd'hui</div>
                <div class="text-3xl font-black text-slate-800">{{ $nbVentesJour }}</div>
            </div>
        </div> --}}

        {{-- <div class="glass-panel p-6 rounded-2xl flex items-center hover:bg-white/80 transition-colors">
            <div class="p-4 bg-emerald-100 text-emerald-600 rounded-xl mr-4 shadow-sm border border-emerald-200">
                <i class="ri-money-dollar-circle-line text-2xl"></i>
            </div>
            <div>
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider">Recettes du Jour</div>
                <div class="text-2xl font-black text-slate-800">{{ number_format($ventesAujourdhui, 0, ',', ' ') }} <span class="text-sm font-medium text-slate-500">{{ param("currency") }}</span></div>
            </div>
        </div> --}}

        <div class="glass-panel p-6 rounded-2xl flex items-center hover:bg-white/80 transition-colors">
            <div class="p-4 bg-rose-100 text-rose-600 rounded-xl mr-4 shadow-sm border border-rose-200">
                <i class="ri-money-cny-box-line text-2xl"></i>
            </div>
            <div>
                <div class="text-sm font-medium text-slate-500 mb-1">Dettes à recouvrer</div>
                <div class="text-2xl font-black text-slate-800">{{ number_format($dettesRestantes ?? 0, 0, ',', ' ') }} {{ param("currency") }}</div>
                <div class="text-sm text-slate-500 mt-1">{{ $dettesCount ?? 0 }} achat(s) en dette</div>
                <div class="text-xs text-slate-400 mt-2">Consultez le suivi des dettes depuis la page dédiée.</div>
            </div>
        </div>
    </div>

    <div class="mb-8">
        <h3 class="text-lg font-bold text-slate-700 mb-4 flex items-center">
            <i class="ri-file-list-line mr-2 text-orange-500"></i> Enregistrer une opération
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('boutiquier.depenses.create') }}" class="glass-panel p-6 rounded-2xl bg-gradient-to-br from-orange-50 to-amber-50 hover:from-orange-100 hover:to-amber-100 transition-all duration-200 shadow-sm hover:shadow-md border border-orange-200/50">
                <div class="flex items-center mb-3">
                    <div class="p-3 bg-orange-100 text-orange-600 rounded-xl mr-4">
                        <i class="ri-alert-line text-xl"></i>
                    </div>
                    <h4 class="text-base font-bold text-slate-800">Déclarer une Perte</h4>
                </div>
                <p class="text-sm text-slate-600">Signaler un produit endommagé, cassé ou perdu du stock de votre boutique.</p>
            </a>
            <div class="h-10"></div>
            <a href="{{ route('boutiquier.depenses.create') }}" class="glass-panel p-6 rounded-2xl bg-gradient-to-br from-violet-50 to-purple-50 hover:from-violet-100 hover:to-purple-100 transition-all duration-200 shadow-sm hover:shadow-md border border-violet-200/50">
                <div class="flex items-center mb-3">
                    <div class="p-3 bg-violet-100 text-violet-600 rounded-xl mr-4">
                        <i class="ri-money-dollar-circle-line text-xl"></i>
                    </div>
                    <h4 class="text-base font-bold text-slate-800">Ajouter une Dépense</h4>
                </div>
                <p class="text-sm text-slate-600">Enregistrer une dépense (frais de transport, entretien, fournitures...) pour votre boutique.</p>
            </a>
        </div>
    </div>

    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-4">
        <h3 class="text-lg font-bold text-slate-700 flex items-center gap-2">
            <i class="ri-grid-line text-blue-500"></i> Saisir une vente directement depuis la liste des produits
        </h3>
        <form id="produit-search-form" action="{{ route('boutiquier.dashboard') }}" method="GET" class="w-full md:w-1/2" role="search">
            <label for="q" class="sr-only">Rechercher produit</label>
            <div class="relative">
                <input id="q" name="q" type="text" value="{{ old('q', $q ?? '') }}" autocomplete="off" placeholder="Rechercher un produit à vendre..." class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 pl-10 pr-10 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100" />
                <i class="ri-search-line absolute left-3 top-3 text-slate-400"></i>
                <button type="button" id="q-clear" class="absolute right-3 top-2.5 hidden text-slate-400 hover:text-slate-600" aria-label="Effacer la recherche">
                    <i class="ri-close-circle-line text-lg"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="mb-8">
        <div class="glass-panel rounded-2xl p-6 bg-white shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                <div class="space-y-2">
                    <p class="text-sm font-medium text-slate-600">Vente pour</p>
                    <div class="flex gap-x-10 ">
                        <label class="inline-flex items-center gap-2 text-slate-700">
                            <input type="radio" name="sale_type" value="client" checked class="text-blue-600 focus:ring-blue-500">
                            Client
                        </label>
                        <div class="w-10"></div>
                        <label class="inline-flex items-center gap-2 text-slate-700">
                            <input type="radio" name="sale_type" value="grossiste" class="text-blue-600 focus:ring-blue-500">
                            Grossiste
                        </label>
                    </div>
                </div>

                <div class="lg:col-span-2" id="grossiste-select-container" style="display: none;">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Grossiste <span class="text-slate-400 font-normal">(optionnel)</span></label>
                    <select id="grossiste-select" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="">-- Prix grossiste par défaut --</option>
                        @foreach($grossistes as $grossiste)
                            <option value="{{ $grossiste->id }}">{{ $grossiste->nom }} ({{ $grossiste->code }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-2">Laissez « Prix grossiste par défaut » pour vendre au tarif grossiste de base, ou choisissez un grossiste pour appliquer ses tarifs personnalisés.</p>
                </div>

                @if($mecaniciens->isNotEmpty())
                    <div class="lg:col-span-2" id="mecanicien-select-container">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Mécanicien <span class="text-slate-400 font-normal">(optionnel)</span>
                        </label>
                        <select id="mecanicien-select" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">-- Aucun mécanicien --</option>
                            @foreach($mecaniciens as $mecanicien)
                                <option value="{{ $mecanicien->id }}">{{ $mecanicien->nom_utilisateur }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-2">Enregistrez la vente au nom d'un mécanicien : il touchera sa commission sur le bénéfice des articles vendus.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mb-8">
        <div class="glass-panel rounded-2xl p-6 bg-white shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Ticket en cours</h3>
                    <p class="text-sm text-slate-500">Ajoutez plusieurs produits puis validez le ticket en une seule opération.</p>
                </div>
                <button id="clear-cart-button" type="button" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 rounded-full hover:bg-slate-200 transition-colors">
                    Vider le ticket
                </button>
            </div>

            <div id="cart-empty" class="text-sm text-slate-500">Aucun produit ajouté au ticket.</div>
            <div id="cart-items" class="space-y-3"></div>

            <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4">
                <div class="text-sm text-slate-500">Total du ticket</div>
                <div class="text-3xl font-black text-slate-900" id="cart-total">0 {{ param("currency") }}</div>
            </div>

            <form id="checkout-form" method="POST" action="{{ route('boutiquier.ventes.store') }}" class="mt-6" data-offline-sync="true">
                @csrf
                <input type="hidden" name="is_grossiste" id="checkout-is-grossiste" value="0">
                <input type="hidden" name="grossiste_id" id="checkout-grossiste-id" value="">
                <input type="hidden" name="mecanicien_id" id="checkout-mecanicien-id" value="">
                {{-- Heure de l'encaissement : sert uniquement au rejeu d'une vente
                     enregistrée hors-ligne (le serveur fait foi en ligne). --}}
                <input type="hidden" name="vendu_a" id="checkout-vendu-a" value="">
                <div id="checkout-line-inputs"></div>
                <button id="checkout-button" type="submit" class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-2xl shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Enregistrer le ticket
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @forelse($produits as $produit)
            @php
                // Somme de TOUS les lots de la boutique (le système FIFO crée plusieurs lignes stock)
                $quantiteStock = $produit->stocks->sum('quantite');
                $enStock = $quantiteStock > 0;
                // Prix grossiste du lot actif (le plus ancien en stock) — utilisé par le point de vente
                $lotActifGrossiste = optional($produit->stocks->where('quantite', '>', 0)->sortBy('created_at')->first())->prix_vente_grossiste_unitaire;
            @endphp
            <div data-produit-id="{{ $produit->id }}" data-client-price="{{ $horsHeures ? \App\Support\TarifHoraire::prixMajore($produit, (float) ($produit->prix_vente ?? 0)) : ($produit->prix_vente ?? 0) }}" data-client-price-standard="{{ $produit->prix_vente ?? 0 }}" data-grossiste-price="{{ $produit->getRawOriginal('prix_vente_grossiste') ?? $lotActifGrossiste ?? '' }}" data-in-stock="{{ $enStock ? 1 : 0 }}" data-search="{{ \Illuminate\Support\Str::lower(trim($produit->nom . ' ' . $produit->reference)) }}" class="product-card glass-panel rounded-2xl p-4 bg-white shadow-sm transition-all duration-200 hover:shadow-lg {{ $enStock ? 'cursor-default' : 'opacity-50 cursor-not-allowed' }}">
                <div class="flex-1">
                    @if($produit->image)
                        <img src="{{ asset('storage/' . $produit->image) }}" alt="{{ $produit->nom }}" class="object-cover rounded-2xl mb-4 w-50 h-40" style="max-height: 12rem;">
                    @else
                        <div class="bg-slate-100 rounded-2xl mb-4 h-40 flex items-center justify-center text-slate-400 border border-slate-200">
                            <i class="ri-image-line text-4xl"></i>
                        </div>
                    @endif

                    <div class="mb-4">
                        <h4 class="text-base font-bold text-slate-800">{{ $produit->nom }}@if($produit->reference) ({{ $produit->reference }})@endif</h4>
                        @if($produit->reference)
                            <p class="text-xs text-slate-500 font-mono bg-slate-50 inline-block px-2 py-1 rounded mt-1">{{ $produit->reference }}</p>
                        @endif
                        <p class="text-blue-600 font-black text-xl mt-2"> <span class="product-price-label">{{ number_format($produit->prix_vente, 0, ',', ' ') }}</span> {{ param("currency") }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $enStock ? 'En stock' : 'Rupture de stock' }}{{ $enStock ? ' • Qté: ' . $quantiteStock : '' }}</p>

                        @php
                            // Lots FIFO disponibles (le plus ancien = prix appliqué en priorité)
                            $lotsFifo = $produit->stocks->where('quantite', '>', 0)->sortBy('created_at')->values();
                        @endphp
                        @if($lotsFifo->count() > 0)
                            <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-2 max-h-28 overflow-y-auto">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-1">Écoulement FIFO — prix par lot <span class="text-slate-400 normal-case">(client / <span class="text-emerald-600">grossiste</span>)</span></p>
                                @foreach($lotsFifo as $i => $lot)
                                    <div class="flex items-center justify-between text-xs py-0.5 {{ $i === 0 ? 'font-bold text-blue-700' : 'text-slate-500' }}">
                                        <span>{{ $i === 0 ? '➜ ' : '' }}{{ $lot->quantite }} u.</span>
                                        <span class="text-right">
                                            {{ number_format($lot->prix_vente_unitaire ?? $produit->prix_vente, 0, ',', ' ') }} F
                                            @if($lot->prix_vente_grossiste_unitaire !== null)
                                                <span class="text-[10px] text-emerald-600">/ {{ number_format($lot->prix_vente_grossiste_unitaire, 0, ',', ' ') }} F</span>
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(($produit->otherBoutiqueStocks ?? collect())->isNotEmpty())
                            <div class="mt-3 rounded-xl display-block border border-amber-200 bg-amber-50 p-3 max-h-40 overflow-y-auto">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Autres boutiques</p>
                                @foreach($produit->otherBoutiqueStocks as $otherStock)
                                    <p class="mt-1 text-xs text-slate-600">
                                        {{ $otherStock->boutique?->nom ?? 'Autre boutique' }} : <span class="font-semibold text-slate-800">{{ $otherStock->quantite }}</span>
                                    </p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mb-4 p-4 rounded-2xl border border-slate-200 bg-slate-50">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Quantité vendue</label>
                        <div class="flex items-center gap-2">
                            <button type="button" data-action="decrease" data-target="qty-{{ $produit->id }}" class="w-11 h-11 bg-slate-200 hover:bg-slate-300 rounded-xl flex items-center justify-center text-xl font-bold text-slate-700 transition-colors" {{ $enStock ? '' : 'disabled' }}>
                                <i class="ri-subtract-line"></i>
                            </button>
                            <input id="qty-{{ $produit->id }}" type="number" name="quantite" value="1" min="1" max="{{ $quantiteStock > 0 ? $quantiteStock : 1 }}" class="qty-input w-20 text-center text-2xl font-black px-3 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none" {{ $enStock ? '' : 'disabled' }}>
                            <button type="button" data-action="increase" data-target="qty-{{ $produit->id }}" class="w-11 h-11 bg-slate-200 hover:bg-slate-300 rounded-xl flex items-center justify-center text-xl font-bold text-slate-700 transition-colors" {{ $enStock ? '' : 'disabled' }}>
                                <i class="ri-add-line"></i>
                            </button>
                        </div>

                        <p class="text-sm text-slate-500 mt-3">Total : <span class="font-bold text-slate-900 total-price">{{ number_format($produit->prix_vente, 0, ',', ' ') }}</span> {{ param("currency") }}</p>
                        <p class="grossiste-note text-sm text-rose-600 mt-2 hidden"></p>
                    </div>
                </div>

                <button type="button" class="add-to-cart-button submit-sale-button w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-2xl shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed" {{ $enStock ? '' : 'disabled' }}>
                    <i class="ri-shopping-cart-line mr-2 text-xl"></i> Ajouter au ticket
                </button>
            </div>
        @empty
            <div class="col-span-full glass-panel rounded-2xl p-12 text-center text-slate-500">
                <i class="ri-shopping-bag-line text-5xl mb-3"></i>
                <p>Aucun produit n'est encore enregistré dans votre boutique.</p>
            </div>
        @endforelse
    </div>

    <div id="search-no-results" class="hidden glass-panel rounded-2xl p-12 text-center text-slate-500">
        <i class="ri-search-eye-line text-5xl mb-3"></i>
        <p>Aucun produit ne correspond à votre recherche.</p>
    </div>
</div>
@php
    $grossistesJson = [];
    foreach ($grossistes as $grossiste) {
        $grossistesJson[] = [
            'id' => $grossiste->id,
            'nom' => $grossiste->nom,
            'code' => $grossiste->code,
            'prix' => $grossiste->prixProduits->pluck('prix_vente', 'produit_id'),
        ];
    }
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const grossistes = @json($grossistesJson);

        const saleTypeInputs = document.querySelectorAll('input[name="sale_type"]');
        const grossisteSelect = document.getElementById('grossiste-select');
        const grossisteContainer = document.getElementById('grossiste-select-container');
        const productCards = document.querySelectorAll('.product-card');
        const cartItemsContainer = document.getElementById('cart-items');
        const cartEmptyMessage = document.getElementById('cart-empty');
        const cartTotalLabel = document.getElementById('cart-total');
        const checkoutForm = document.getElementById('checkout-form');
        const checkoutLineInputs = document.getElementById('checkout-line-inputs');
        const checkoutButton = document.getElementById('checkout-button');
        const checkoutIsGrossiste = document.getElementById('checkout-is-grossiste');
        const checkoutGrossisteId = document.getElementById('checkout-grossiste-id');
        const checkoutMecanicienId = document.getElementById('checkout-mecanicien-id');
        const checkoutVenduA = document.getElementById('checkout-vendu-a');
        const mecanicienSelect = document.getElementById('mecanicien-select');
        const mecanicienContainer = document.getElementById('mecanicien-select-container');
        const clearCartButton = document.getElementById('clear-cart-button');

        let cart = {};

        function getSaleType() {
            return document.querySelector('input[name="sale_type"]:checked')?.value || 'client';
        }

        function getSelectedGrossisteId() {
            return grossisteSelect ? grossisteSelect.value : '';
        }

        function findGrossiste(id) {
            return grossistes.find(g => String(g.id) === String(id));
        }

        function updateCartDisplay() {
            const entries = Object.values(cart);
            const total = entries.reduce((sum, item) => sum + item.unitPrice * item.quantite, 0);
            const saleType = getSaleType();

            cartItemsContainer.innerHTML = '';
            if (!entries.length) {
                cartEmptyMessage.style.display = 'block';
                checkoutButton.disabled = true;
                checkoutLineInputs.innerHTML = '';
            } else {
                cartEmptyMessage.style.display = 'none';
                checkoutButton.disabled = false;
                checkoutLineInputs.innerHTML = '';

                entries.forEach((item, index) => {
                    const line = document.createElement('div');
                    line.className = 'rounded-2xl border border-slate-200 p-4 bg-slate-50 flex items-center justify-between gap-4';
                    line.innerHTML = `
                        <div class="min-w-0">
                            <div class="font-semibold text-slate-900 truncate">${item.nom}</div>
                            <div class="text-xs text-slate-500">${new Intl.NumberFormat('fr-FR').format(item.unitPrice)} {{ param("currency") }} / u.</div>
                            <div class="mt-2 flex items-center gap-2">
                                <button type="button" data-action="cart-decrease" data-produit-id="${item.produitId}" class="w-8 h-8 bg-slate-200 hover:bg-slate-300 rounded-lg flex items-center justify-center text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed" ${item.quantite <= 1 ? 'disabled' : ''} aria-label="Diminuer">
                                    <i class="ri-subtract-line"></i>
                                </button>
                                <span class="w-8 text-center font-bold text-slate-900">${item.quantite}</span>
                                <button type="button" data-action="cart-increase" data-produit-id="${item.produitId}" class="w-8 h-8 bg-slate-200 hover:bg-slate-300 rounded-lg flex items-center justify-center text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed" ${item.maxStock && item.quantite >= item.maxStock ? 'disabled' : ''} aria-label="Augmenter">
                                    <i class="ri-add-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-slate-800 font-bold">${new Intl.NumberFormat('fr-FR').format(item.unitPrice * item.quantite)} {{ param("currency") }}</div>
                            <button type="button" data-action="remove-cart-item" data-produit-id="${item.produitId}" class="mt-2 text-xs text-rose-600 hover:text-rose-800">Supprimer</button>
                        </div>
                    `;
                    cartItemsContainer.appendChild(line);

                    const inputProduit = document.createElement('input');
                    inputProduit.type = 'hidden';
                    inputProduit.name = `lignes[${index}][produit_id]`;
                    inputProduit.value = item.produitId;
                    checkoutLineInputs.appendChild(inputProduit);

                    const inputQuantite = document.createElement('input');
                    inputQuantite.type = 'hidden';
                    inputQuantite.name = `lignes[${index}][quantite]`;
                    inputQuantite.value = item.quantite;
                    checkoutLineInputs.appendChild(inputQuantite);
                });
            }

            cartTotalLabel.textContent = new Intl.NumberFormat('fr-FR').format(total) + ' {{ param("currency") }}';
            // Horodatage de l'encaissement : utilisé uniquement si la vente est
            // rejouée plus tard (mode hors-ligne), pour que la tarification hors
            // heures corresponde au moment réel de la vente.
            if (checkoutVenduA) {
                const d = new Date();
                const p = (n) => String(n).padStart(2, '0');
                checkoutVenduA.value = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate())
                    + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
            }
            checkoutIsGrossiste.value = getSaleType() === 'grossiste' ? '1' : '0';
            checkoutGrossisteId.value = getSaleType() === 'grossiste' ? getSelectedGrossisteId() : '';
            // Le mécanicien n'est crédité que sur une vente CLIENT.
            if (checkoutMecanicienId) {
                checkoutMecanicienId.value = (getSaleType() === 'client' && mecanicienSelect) ? mecanicienSelect.value : '';
            }
        }

        function updateCard(card) {
            const productId = card.dataset.produitId;
            const quantityInput = card.querySelector('.qty-input');
            const priceLabel = card.querySelector('.product-price-label');
            const totalPrice = card.querySelector('.total-price');
            const grossisteNote = card.querySelector('.grossiste-note');
            const submitBtn = card.querySelector('.submit-sale-button');
            let unitPrice = parseFloat(card.dataset.clientPrice) || 0;
            const saleType = getSaleType();
            const quantity = parseInt(quantityInput.value, 10) || 1;
            const selectedGrossisteId = getSelectedGrossisteId();

            if (saleType === 'grossiste') {
                const grossiste = findGrossiste(selectedGrossisteId);
                const clientPrice = parseFloat(card.dataset.clientPrice) || 0;
                const lotGrossistePrice = parseFloat(card.dataset.grossistePrice);
                const hasLotGrossiste = !isNaN(lotGrossistePrice) && lotGrossistePrice > 0;

                // Prix grossiste PAR DÉFAUT du produit : prix grossiste du lot, sinon prix client.
                const defaultGrossiste = hasLotGrossiste ? lotGrossistePrice : clientPrice;

                // Tarif SPÉCIFIQUE de ce grossiste (override) s'il est défini et > 0.
                const override = grossiste ? parseFloat(grossiste.prix[productId]) : NaN;
                const hasOverride = !isNaN(override) && override > 0;

                if (hasOverride) {
                    unitPrice = override;
                    grossisteNote.textContent = 'Tarif ' + grossiste.nom + ' (' + grossiste.code + ')';
                } else {
                    unitPrice = defaultGrossiste;
                    grossisteNote.textContent = grossiste
                        ? 'Prix grossiste par défaut (aucun tarif spécifique pour ' + grossiste.nom + ')'
                        : 'Prix grossiste par défaut';
                }

                grossisteNote.classList.remove('hidden', 'text-rose-600');
                grossisteNote.classList.add('text-slate-500');

                // Seul le stock conditionne la vente (le prix grossiste existe toujours).
                submitBtn.disabled = !(card.dataset.inStock === '1');
            } else {
                submitBtn.disabled = !(card.dataset.inStock === '1');
                grossisteNote.classList.add('hidden');
            }

            priceLabel.textContent = new Intl.NumberFormat('fr-FR').format(unitPrice);
            totalPrice.textContent = new Intl.NumberFormat('fr-FR').format(unitPrice * quantity);
        }

        function updateAllCards() {
            const saleType = getSaleType();
            if (grossisteContainer) {
                grossisteContainer.style.display = saleType === 'grossiste' ? 'block' : 'none';
            }
            // Le mécanicien ne concerne que les ventes client.
            if (mecanicienContainer) {
                mecanicienContainer.style.display = saleType === 'client' ? 'block' : 'none';
            }
            productCards.forEach(updateCard);
            updateCartDisplay();
        }

        function addToCart(card) {
            const productId = card.dataset.produitId;
            const quantityInput = card.querySelector('.qty-input');
            const priceLabel = card.querySelector('.product-price-label');
            const quantity = parseInt(quantityInput.value, 10) || 1;
            const maxStock = parseInt(quantityInput.getAttribute('max'), 10) || quantity;
            const unitPrice = Number(priceLabel.textContent.replace(/\s/g, '').replace('{{ param("currency") }}', '')) || parseFloat(card.dataset.clientPrice) || 0;
            const productName = card.querySelector('h4')?.textContent.trim() || 'Produit';

            if (!cart[productId]) {
                cart[productId] = {
                    produitId: productId,
                    quantite: Math.min(quantity, maxStock),
                    unitPrice,
                    maxStock,
                    nom: productName,
                };
            } else {
                // Ne jamais dépasser le stock disponible
                cart[productId].quantite = Math.min(cart[productId].quantite + quantity, maxStock);
                cart[productId].unitPrice = unitPrice;
                cart[productId].maxStock = maxStock;
            }

            updateCartDisplay();
        }

        function clearCart() {
            cart = {};
            updateCartDisplay();
        }

        saleTypeInputs.forEach(input => input.addEventListener('change', updateAllCards));
        if (grossisteSelect) {
            grossisteSelect.addEventListener('change', updateAllCards);
        }
        if (mecanicienSelect) {
            mecanicienSelect.addEventListener('change', updateCartDisplay);
        }

        productCards.forEach(card => {
            const quantityInput = card.querySelector('.qty-input');
            const decreaseButton = card.querySelector('[data-action="decrease"]');
            const increaseButton = card.querySelector('[data-action="increase"]');
            const addToCartButton = card.querySelector('.add-to-cart-button');

            if (quantityInput) {
                quantityInput.addEventListener('input', () => updateCard(card));
            }

            const updateQuantity = (delta) => {
                if (!quantityInput) return;
                let currentValue = parseInt(quantityInput.value, 10) || 1;
                const min = parseInt(quantityInput.getAttribute('min'), 10) || 1;
                const max = parseInt(quantityInput.getAttribute('max'), 10) || currentValue;
                currentValue = Math.min(Math.max(currentValue + delta, min), max);
                quantityInput.value = currentValue;
                updateCard(card);
            };

            if (decreaseButton) {
                decreaseButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    updateQuantity(-1);
                });
            }

            if (increaseButton) {
                increaseButton.addEventListener('click', (event) => {
                    event.preventDefault();
                    updateQuantity(1);
                });
            }

            if (addToCartButton) {
                addToCartButton.addEventListener('click', () => addToCart(card));
            }
        });

        if (clearCartButton) {
            clearCartButton.addEventListener('click', clearCart);
        }

        // --- Recherche instantanée côté client (filtre la grille, aucun rechargement) ---
        const searchInput = document.getElementById('q');
        const searchForm = document.getElementById('produit-search-form');
        const searchClear = document.getElementById('q-clear');
        const noResults = document.getElementById('search-no-results');

        function normalizeText(value) {
            // Insensible aux accents : "moteur" trouve "Moteur", "démarreur" trouve "demarreur"
            return (value || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function applyProductFilter() {
            const term = normalizeText(searchInput ? searchInput.value.trim() : '');
            let visible = 0;
            productCards.forEach(card => {
                const haystack = normalizeText(card.getAttribute('data-search'));
                const match = term === '' || haystack.includes(term);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (noResults) {
                noResults.classList.toggle('hidden', visible !== 0 || productCards.length === 0);
            }
            if (searchClear) {
                searchClear.classList.toggle('hidden', term === '');
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyProductFilter);
            // Entrée ne recharge plus la page : on filtre en direct
            if (searchForm) {
                searchForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    applyProductFilter();
                });
            }
            if (searchClear) {
                searchClear.addEventListener('click', () => {
                    searchInput.value = '';
                    applyProductFilter();
                    searchInput.focus();
                });
            }
            applyProductFilter();
        }

        cartItemsContainer.addEventListener('click', (event) => {
            const removeBtn = event.target.closest('[data-action="remove-cart-item"]');
            if (removeBtn) {
                const productId = removeBtn.dataset.produitId;
                if (cart[productId]) {
                    delete cart[productId];
                    updateCartDisplay();
                }
                return;
            }

            const decreaseBtn = event.target.closest('[data-action="cart-decrease"]');
            if (decreaseBtn) {
                const productId = decreaseBtn.dataset.produitId;
                // Décrémente sans jamais passer sous 1 (utiliser "Supprimer" pour retirer)
                if (cart[productId] && cart[productId].quantite > 1) {
                    cart[productId].quantite -= 1;
                    updateCartDisplay();
                }
                return;
            }

            const increaseBtn = event.target.closest('[data-action="cart-increase"]');
            if (increaseBtn) {
                const productId = increaseBtn.dataset.produitId;
                // Incrémente sans jamais dépasser le stock disponible
                if (cart[productId]) {
                    const max = cart[productId].maxStock || Infinity;
                    if (cart[productId].quantite < max) {
                        cart[productId].quantite += 1;
                        updateCartDisplay();
                    }
                }
                return;
            }
        });

        updateAllCards();
    });
</script>
@endsection
