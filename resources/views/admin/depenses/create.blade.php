@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm mb-4 inline-flex items-center">
        <i class="ri-arrow-left-line mr-2"></i> Retour au tableau de bord
    </a>
    <h1 class="text-3xl font-bold text-slate-900 mb-3">Créer une dépense personnelle</h1>
    <p class="text-sm text-slate-600 max-w-2xl">Choisissez la boutique qui sera débitée et notifiez ses responsables pour qu’ils déclarent une perte correspondante.</p>
</div>

@if(session('success'))
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-900">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-white shadow rounded-3xl p-8 max-w-3xl">
    <form action="{{ route('admin.depenses.store') }}" method="POST">
        @csrf

        <div class="grid gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Boutique débitée <span class="text-red-500">*</span></label>
                <select name="boutique_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">-- Sélectionner une boutique --</option>
                    @foreach($boutiques as $boutique)
                        <option value="{{ $boutique->id }}" {{ old('boutique_id') == $boutique->id ? 'selected' : '' }}>{{ $boutique->nom }} (Solde: {{ number_format($boutique->solde, 0, ',', ' ') }} FCFA)</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Montant (FCFA) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="montant" value="{{ old('montant') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 bg-white focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: 10000" required>
            </div>
        </div>

        <div class="mb-6 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-700">
            <p class="font-medium">Notification automatique</p>
            <p class="text-sm">La boutique sélectionnée recevra un e-mail et une notification dans l’application pour déclarer une dépense correspondante.</p>
        </div>

        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow hover:bg-blue-700 transition">Créer la dépense et notifier la boutique</button>
    </form>
</div>
@endsection
