@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Sessions principales des employés</h2>
    <p class="text-slate-600">Définissez la session principale (heures normales) de chaque rôle par jour. Travailler en dehors = heures supplémentaires majorées au profit de l'employé.</p>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
        <i class="ri-checkbox-circle-line text-lg mr-2"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Tranches pour Magasiniers -->
    <div class="glass-panel rounded-2xl overflow-hidden">
        <div class="p-6 bg-white/50 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-xl text-slate-800">Magasiniers</h3>
                    <p class="text-sm text-slate-600 mt-1">Heures d'accès au système</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="ri-store-2-line text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Formulaire d'ajout -->
            <form action="{{ route('admin.horaires.store') }}" method="POST" class="mb-6 pb-6 border-b border-slate-200">
                @csrf
                <input type="hidden" name="role" value="magasinier">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Jour <span class="text-red-500">*</span></label>
                        <select name="jour_semaine" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="0">Lundi</option>
                            <option value="1">Mardi</option>
                            <option value="2">Mercredi</option>
                            <option value="3">Jeudi</option>
                            <option value="4">Vendredi</option>
                            <option value="5">Samedi</option>
                            <option value="6">Dimanche</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Heure début <span class="text-red-500">*</span></label>
                        <input type="time" name="heure_debut" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Heure fin <span class="text-red-500">*</span></label>
                        <input type="time" name="heure_fin" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center justify-center">
                            <i class="ri-add-line mr-1"></i> Ajouter la session
                        </button>
                    </div>
                </div>

                @if($errors->has('jour_semaine') || $errors->has('heure_debut') || $errors->has('heure_fin'))
                    <div class="text-red-600 text-xs mt-2">
                        @foreach($errors->get('jour_semaine') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                        @foreach($errors->get('heure_debut') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                        @foreach($errors->get('heure_fin') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            </form>

            <!-- Liste des tranches -->
            @if($magasiniers->isNotEmpty())
                <div class="space-y-3">
                    @foreach($magasiniers as $horaire)
                        <div class="flex items-center justify-between p-3 bg-white/40 border border-slate-200 rounded-lg group hover:bg-white/60 transition-colors">
                            <div class="flex-1">
                                <div class="font-medium text-slate-800">{{ $horaire->getDayLabel() }}</div>
                                <div class="text-sm text-slate-600">{{ $horaire->heure_debut }} - {{ $horaire->heure_fin }}</div>
                                <span class="mt-1 inline-flex items-center rounded-full bg-slate-100 text-slate-600 px-2 py-0.5 text-[10px] font-semibold">
                                    <i class="ri-sun-line mr-1"></i> Session principale
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <form action="{{ route('admin.horaires.toggle', $horaire) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-2 rounded-lg transition-colors {{ $horaire->actif ? 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}" title="{{ $horaire->actif ? 'Désactiver' : 'Activer' }}">
                                        <i class="ri-{{ $horaire->actif ? 'check-line' : 'close-line' }}"></i>
                                    </button>
                                </form>

                                <form action="{{ route('admin.horaires.destroy', $horaire) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6">
                    <i class="ri-inbox-line text-4xl text-slate-300 mb-2"></i>
                    <p class="text-slate-500 text-sm">Aucune session définie</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Tranches pour Boutiquiers -->
    <div class="glass-panel rounded-2xl overflow-hidden">
        <div class="p-6 bg-white/50 border-b border-slate-200/50">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-xl text-slate-800">Boutiquiers</h3>
                    <p class="text-sm text-slate-600 mt-1">Heures d'accès au système</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center">
                    <i class="ri-store-2-line text-amber-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Formulaire d'ajout -->
            <form action="{{ route('admin.horaires.store') }}" method="POST" class="mb-6 pb-6 border-b border-slate-200">
                @csrf
                <input type="hidden" name="role" value="boutiquier">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Jour <span class="text-red-500">*</span></label>
                        <select name="jour_semaine" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="0">Lundi</option>
                            <option value="1">Mardi</option>
                            <option value="2">Mercredi</option>
                            <option value="3">Jeudi</option>
                            <option value="4">Vendredi</option>
                            <option value="5">Samedi</option>
                            <option value="6">Dimanche</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Heure début <span class="text-red-500">*</span></label>
                        <input type="time" name="heure_debut" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Heure fin <span class="text-red-500">*</span></label>
                        <input type="time" name="heure_fin" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm" required>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center justify-center">
                            <i class="ri-add-line mr-1"></i> Ajouter la session
                        </button>
                    </div>
                </div>

                @if($errors->has('jour_semaine') || $errors->has('heure_debut') || $errors->has('heure_fin'))
                    <div class="text-red-600 text-xs mt-2">
                        @foreach($errors->get('jour_semaine') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                        @foreach($errors->get('heure_debut') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                        @foreach($errors->get('heure_fin') as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
            </form>

            <!-- Liste des tranches -->
            @if($boutiquiers->isNotEmpty())
                <div class="space-y-3">
                    @foreach($boutiquiers as $horaire)
                        <div class="flex items-center justify-between p-3 bg-white/40 border border-slate-200 rounded-lg group hover:bg-white/60 transition-colors">
                            <div class="flex-1">
                                <div class="font-medium text-slate-800">{{ $horaire->getDayLabel() }}</div>
                                <div class="text-sm text-slate-600">{{ $horaire->heure_debut }} - {{ $horaire->heure_fin }}</div>
                                <span class="mt-1 inline-flex items-center rounded-full bg-slate-100 text-slate-600 px-2 py-0.5 text-[10px] font-semibold">
                                    <i class="ri-sun-line mr-1"></i> Session principale
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <form action="{{ route('admin.horaires.toggle', $horaire) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="p-2 rounded-lg transition-colors {{ $horaire->actif ? 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}" title="{{ $horaire->actif ? 'Désactiver' : 'Activer' }}">
                                        <i class="ri-{{ $horaire->actif ? 'check-line' : 'close-line' }}"></i>
                                    </button>
                                </form>

                                <form action="{{ route('admin.horaires.destroy', $horaire) }}" method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-6">
                    <i class="ri-inbox-line text-4xl text-slate-300 mb-2"></i>
                    <p class="text-slate-500 text-sm">Aucune session définie</p>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="mt-8 p-6 bg-blue-50 border border-blue-200 rounded-2xl">
    <div class="flex gap-4">
        <i class="ri-information-line text-blue-600 text-2xl flex-shrink-0"></i>
        <div>
            <h4 class="font-bold text-blue-900 mb-2">Session principale &amp; connexion</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li>Chaque plage définit la <strong>session principale</strong> de l'employé pour ce jour (ses heures normales).</li>
                <li><strong>Les jours où il a une session</strong>, l'employé peut se connecter <strong>à toute heure</strong> : il peut arriver plus tôt ou rester plus tard pour faire des heures supplémentaires.</li>
                <li><strong>Les jours sans session</strong> (repos), l'accès reste bloqué. <strong>Les admins</strong> se connectent toujours, sans restriction.</li>
                <li>Le retard (arrivée après le début) et le départ anticipé (avant la fin) restent <strong>pénalisés par une déduction</strong>, calculée par rapport à la session principale.</li>
            </ul>

            <h4 class="font-bold text-blue-900 mt-4 mb-2">Heures supplémentaires (majoration automatique)</h4>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li>Toute vente réalisée <strong>en dehors de la session principale</strong> — avant le début ou après la fin — est automatiquement <strong>majorée</strong>. Aucune tranche spéciale à créer.</li>
                <li>La différence avec le prix normal <strong>revient à l'employé</strong> qui réalise la vente, cumulée et payée en fin de mois.</li>
                <li>Exemple : session <strong>07:00 – 19:00</strong>. Une vente à 06:30 (arrivé plus tôt) ou à 19:30 (reparti plus tard) est majorée. À 19:00 pile le prix reste normal ; la majoration démarre à 19:01.</li>
                <li>Le montant de la majoration se règle par produit (« prix hors heures ») ou globalement en pourcentage dans <a href="{{ route('admin.parametres.index') }}" class="underline font-semibold">Paramètres</a>.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
