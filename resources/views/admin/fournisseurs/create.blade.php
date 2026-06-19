@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.fournisseurs.index') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à la liste
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">
        {{ isset($fournisseur) ? 'Modifier le Fournisseur' : 'Ajouter un Fournisseur' }}
    </h2>
</div>

<div class="glass-panel rounded-2xl p-8 max-w-3xl">
    <form action="{{ isset($fournisseur) ? route('admin.fournisseurs.update', $fournisseur) : route('admin.fournisseurs.store') }}" method="POST">
        @csrf
        @if(isset($fournisseur))
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Nom de l'entreprise / fournisseur <span class="text-red-500">*</span></label>
            <input type="text" name="nom" value="{{ old('nom', $fournisseur->nom ?? '') }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: Cimenterie X" required>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-medium text-slate-700 mb-2">Contact (Téléphone / Email)</label>
            <input type="text" name="contact" value="{{ old('contact', $fournisseur->contact ?? '') }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ex: 699000000">
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg transform hover:-translate-y-0.5">
                {{ isset($fournisseur) ? 'Mettre à jour' : 'Enregistrer le fournisseur' }}
            </button>
        </div>
    </form>
</div>
@endsection
