@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.depenses.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm mb-4 inline-flex items-center">
        <i class="ri-arrow-left-line mr-2"></i> Retour à l'historique
    </a>
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Modifier la dépense</h1>
    <p class="text-sm text-slate-600">
        {{ $depense->boutique?->nom ?? 'Boutique inconnue' }} · déclarée par {{ $depense->user?->nom_utilisateur ?? '—' }} · {{ $depense->created_at->format('d/m/Y H:i') }}
    </p>
</div>

@if($errors->any())
    <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-900">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($depense->statut === 'approved')
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm flex items-start">
        <i class="ri-alert-line text-lg mr-2 mt-0.5"></i>
        <div>
            <p class="font-semibold">Dépense déjà validée</p>
            <p>Le solde de la boutique a été débité. Si vous changez le montant, <strong>l'écart sera automatiquement répercuté sur le solde</strong>.</p>
        </div>
    </div>
@elseif($depense->statut === 'attente_boutique')
    <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-800 text-sm flex items-start">
        <i class="ri-information-line text-lg mr-2 mt-0.5"></i>
        <div>
            <p class="font-semibold">En attente de validation par la boutique</p>
            <p>La demande de validation du débit sera mise à jour avec le nouveau montant.</p>
        </div>
    </div>
@endif

<div class="bg-white shadow rounded-3xl p-8 max-w-3xl">
    <form action="{{ route('admin.depenses.update', $depense) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Intitulé <span class="text-red-500">*</span></label>
                <input type="text" name="intitule" maxlength="255" value="{{ old('intitule', $depense->intitule) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                <textarea name="description" rows="3" maxlength="1000" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 outline-none">{{ old('description', $depense->description) }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Montant ({{ param("currency") }}) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="1" name="montant" value="{{ old('montant', $depense->montant) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow hover:bg-blue-700 transition">
                <i class="ri-save-line mr-2"></i> Enregistrer
            </button>
            <a href="{{ route('admin.depenses.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-200 px-6 py-3 text-sm font-bold text-slate-800 hover:bg-slate-300 transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
