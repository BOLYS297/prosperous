@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm mb-4 inline-flex items-center">
        <i class="ri-arrow-left-line mr-2"></i> Retour au tableau de bord
    </a>
    <h1 class="text-3xl font-bold text-slate-900 mb-3">Paramètres de l'entreprise</h1>
    <p class="text-sm text-slate-600 max-w-2xl">Nom, immatriculation, devise, logo et bannière apparaissent sur l'application et sur les tickets/factures. Vous pouvez aussi renommer vos points de vente.</p>
</div>

@if(session('success'))
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900 flex items-center">
        <i class="ri-check-line text-lg mr-2"></i>{{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-900">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Identité de l'entreprise -->
<form action="{{ route('admin.parametres.update') }}" method="POST" enctype="multipart/form-data" class="glass-panel rounded-2xl p-6 mb-8">
    @csrf
    @method('PUT')

    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center">
        <i class="ri-building-2-line text-2xl text-blue-600 mr-2"></i> Identité
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Nom de l'entreprise <span class="text-rose-500">*</span></label>
            <input type="text" name="company_name" required value="{{ old('company_name', $settings['company_name'] ?? '') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Immatriculation (sur les tickets)</label>
            <input type="text" name="company_immatriculation" value="{{ old('company_immatriculation', $settings['company_immatriculation'] ?? '') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Devise <span class="text-rose-500">*</span></label>
            <input type="text" name="currency" required maxlength="10" value="{{ old('currency', $settings['currency'] ?? 'FCFA') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-slate-400 mt-1">Ex : FCFA, €, $. Utilisée sur les tickets.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Téléphone</label>
            <input type="text" name="company_phone" value="{{ old('company_phone', $settings['company_phone'] ?? '') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">Adresse</label>
            <input type="text" name="company_address" value="{{ old('company_address', $settings['company_address'] ?? '') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-2">Message de bas de ticket</label>
            <input type="text" name="ticket_footer" value="{{ old('ticket_footer', $settings['ticket_footer'] ?? '') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Commission mécanicien par défaut (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="mecanicien_commission_percent" value="{{ old('mecanicien_commission_percent', $settings['mecanicien_commission_percent'] ?? 10) }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-slate-400 mt-1">Valeur proposée à la création d'un mécanicien. Chaque mécanicien garde son propre pourcentage.</p>
        </div>
    </div>

    <h2 class="text-xl font-bold text-slate-800 mt-8 mb-2 flex items-center">
        <i class="ri-time-line text-2xl text-blue-600 mr-2"></i> Heures d'ouverture &amp; tarif hors heures
    </h2>
    <p class="text-sm text-slate-500 mb-6">
        En dehors de cette plage, le point de vente applique automatiquement le <strong>prix hors heures</strong>.
        La différence avec le prix normal est <strong>reversée à l'employé</strong> qui réalise la vente (heures supplémentaires),
        cumulée et payée en fin de mois.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Heure d'ouverture <span class="text-rose-500">*</span></label>
            <input type="time" name="heure_ouverture" required value="{{ old('heure_ouverture', $settings['heure_ouverture'] ?? '07:00') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-slate-400 mt-1">Avant cette heure, le tarif hors heures s'applique.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Heure de fermeture <span class="text-rose-500">*</span></label>
            <input type="time" name="heure_fermeture" required value="{{ old('heure_fermeture', $settings['heure_fermeture'] ?? '19:00') }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-slate-400 mt-1">Le tarif hors heures démarre à la minute suivante.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Majoration par défaut (%)</label>
            <input type="number" step="0.01" min="0" max="500" name="majoration_hors_heures_percent" value="{{ old('majoration_hors_heures_percent', $settings['majoration_hors_heures_percent'] ?? 20) }}"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <p class="text-xs text-slate-400 mt-1">Appliquée aux produits <strong>sans</strong> prix hors heures saisi. 0 = aucune majoration.</p>
        </div>
    </div>

    <h2 class="text-xl font-bold text-slate-800 mt-8 mb-6 flex items-center">
        <i class="ri-image-line text-2xl text-blue-600 mr-2"></i> Images
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Logo</label>
            <div class="flex items-center gap-4">
                <img src="{{ param_image('logo_path', 'logo.jpg') }}" alt="Logo" class="h-16 w-16 object-contain rounded-lg border border-slate-200 bg-white p-1">
                <input type="file" name="logo" accept="image/*"
                    class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <p class="text-xs text-slate-400 mt-1">JPG, PNG ou WEBP, max 2 Mo.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">Bannière de couverture</label>
            <div class="flex items-center gap-4">
                <img src="{{ param_image('banner_path', 'magasinier-bg.png') }}" alt="Bannière" class="h-16 w-28 object-cover rounded-lg border border-slate-200 bg-white">
                <input type="file" name="banner" accept="image/*"
                    class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <p class="text-xs text-slate-400 mt-1">JPG, PNG ou WEBP, max 4 Mo.</p>
        </div>
    </div>

    <div class="mt-8 flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center">
            <i class="ri-save-line mr-2"></i> Enregistrer les paramètres
        </button>
    </div>
</form>

<!-- Points de vente -->
<div class="glass-panel rounded-2xl p-6">
    <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center">
        <i class="ri-store-2-line text-2xl text-blue-600 mr-2"></i> Points de vente
    </h2>
    <p class="text-sm text-slate-500 mb-6">Renommez vos boutiques et magasins. Le nom apparaît partout dans l'application.</p>

    <div class="space-y-4">
        @forelse($boutiques as $boutique)
            <form action="{{ route('admin.boutiques.update', $boutique) }}" method="POST"
                class="flex flex-col sm:flex-row sm:items-end gap-3 p-4 rounded-xl border border-slate-200 bg-white/60">
                @csrf
                @method('PUT')
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nom</label>
                    <input type="text" name="nom" required value="{{ $boutique->nom }}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="sm:w-48">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="boutique" {{ $boutique->type === 'boutique' ? 'selected' : '' }}>Boutique</option>
                        <option value="magasin" {{ $boutique->type === 'magasin' ? 'selected' : '' }}>Magasin</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition-colors font-medium flex items-center justify-center">
                    <i class="ri-save-line mr-2"></i> Enregistrer
                </button>
            </form>
        @empty
            <p class="text-slate-500">Aucune boutique enregistrée.</p>
        @endforelse
    </div>
</div>
@endsection
