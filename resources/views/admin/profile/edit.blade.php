@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Mon profil</h2>
    <p class="text-sm text-slate-500">Mettez à jour vos identifiants de connexion.</p>
</div>

<div class="glass-panel rounded-2xl p-8">
    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nom d'utilisateur</label>
                <input type="text" name="nom_utilisateur" value="{{ old('nom_utilisateur', $user->nom_utilisateur) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Adresse e-mail</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nouveau mot de passe</label>
                <input type="password" name="password" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none">
                <p class="text-xs text-slate-500 mt-2">Laissez vide pour garder votre mot de passe actuel.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
