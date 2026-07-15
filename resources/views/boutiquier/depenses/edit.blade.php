@extends('layouts.boutiquier')

@section('content')
<div class="mb-8">
    <a href="{{ route('boutiquier.depenses.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à mes dépenses
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Modifier la dépense</h2>
    <p class="text-sm text-slate-500">Déclarée le {{ $depense->created_at->format('d/m/Y à H:i') }}</p>
</div>

@if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm flex items-start">
    <i class="ri-information-line text-lg mr-2 mt-0.5"></i>
    <p>Cette dépense n'a pas encore été traitée par l'administrateur : vous pouvez encore la modifier. Une fois validée ou rejetée, seul l'admin pourra la modifier.</p>
</div>

<div class="glass-panel rounded-2xl p-8 max-w-xl">
    <form action="{{ route('boutiquier.depenses.update', $depense) }}" method="POST" data-offline-sync="true">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Intitulé <span class="text-red-500">*</span></label>
            <input type="text" name="intitule" maxlength="255" value="{{ old('intitule', $depense->intitule) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Description</label>
            <textarea name="description" rows="3" maxlength="1000" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none">{{ old('description', $depense->description) }}</textarea>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Montant ({{ param("currency") }}) <span class="text-red-500">*</span></label>
            <input type="number" step="0.01" min="1" name="montant" value="{{ old('montant', $depense->montant) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none text-2xl font-black text-slate-800" required>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center">
                <i class="ri-save-line mr-2"></i> Enregistrer
            </button>
            <a href="{{ route('boutiquier.depenses.index') }}" class="px-6 py-3 bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300 transition-colors font-medium flex items-center">Annuler</a>
        </div>
    </form>
</div>
@endsection
