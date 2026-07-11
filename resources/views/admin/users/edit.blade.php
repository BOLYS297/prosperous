@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.users.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à la liste
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Modifier un Employé</h2>
</div>

<div class="glass-panel rounded-2xl p-8 max-w-3xl">
    <form action="{{ route('admin.users.update', $user) }}" method="POST">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nom d'utilisateur</label>
                <input type="text" name="nom_utilisateur" value="{{ old('nom_utilisateur', $user->nom_utilisateur) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Adresse Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Mot de passe</label>
                <input type="text" name="password" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Laissez vide pour conserver le mot de passe actuel">
                <p class="text-xs text-slate-500 mt-1">Si vous laissez ce champ vide, le mot de passe actuel sera conservé.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Salaire mensuel ({{ param("currency") }})</label>
                <input type="number" min="0" name="monthly_salary" value="{{ old('monthly_salary', $user->monthly_salary) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8" x-data="{ role: '{{ old('role', $user->role) }}' }">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Rôle</label>
                <select name="role" x-model="role" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
                    <option value="boutiquier" {{ $user->role === 'boutiquier' ? 'selected' : '' }}>Boutiquier</option>
                    <option value="magasinier" {{ $user->role === 'magasinier' ? 'selected' : '' }}>Magasinier</option>
                </select>
            </div>

            <div x-show="role === 'boutiquier' || role === 'magasinier'" class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Plages horaires autorisées</label>
                <div class="grid grid-cols-1 gap-3">
                    <div x-show="role === 'magasinier'">
                        @if($magasiniers->isEmpty())
                            <p class="text-sm text-slate-500">Aucun horaire actif défini pour les magasiniers.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($magasiniers as $horaire)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50">
                                        <input type="checkbox" name="horaires[]" value="{{ $horaire->id }}"
                                            @if(in_array($horaire->id, $selectedHoraires) || (is_array(old('horaires')) && in_array($horaire->id, old('horaires'))))
                                                checked
                                            @endif
                                        >
                                        <span>{{ $horaire->getDayLabel() }} — {{ $horaire->heure_debut }} à {{ $horaire->heure_fin }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div x-show="role === 'boutiquier'">
                        @if($boutiquiers->isEmpty())
                            <p class="text-sm text-slate-500">Aucun horaire actif défini pour les boutiquiers.</p>
                        @else
                            <div class="space-y-2">
                                @foreach($boutiquiers as $horaire)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-slate-50">
                                        <input type="checkbox" name="horaires[]" value="{{ $horaire->id }}"
                                            @if(in_array($horaire->id, $selectedHoraires) || (is_array(old('horaires')) && in_array($horaire->id, old('horaires'))))
                                                checked
                                            @endif
                                        >
                                        <span>{{ $horaire->getDayLabel() }} — {{ $horaire->heure_debut }} à {{ $horaire->heure_fin }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-2"><i class="ri-information-line"></i> Sélectionnez au moins une tranche horaire parmi celles définies pour le rôle.</p>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-2">Assignation Boutique/Magasin</label>
                <select name="boutique_id" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">-- Sélectionner une boutique --</option>
                    @foreach($boutiques as $boutique)
                        <option value="{{ $boutique->id }}" {{ old('boutique_id', $user->boutique_id) == $boutique->id ? 'selected' : '' }}>{{ $boutique->nom }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('admin.users.index') }}" class="px-6 py-3 bg-slate-300 text-slate-700 font-medium rounded-xl hover:bg-slate-400 transition-all">
                Annuler
            </a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg transform hover:-translate-y-0.5">
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>
@endsection
