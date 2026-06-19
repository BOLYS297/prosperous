@extends('layouts.admin')

@section('content')
<div class="mb-8 flex justify-between items-end">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Employés & Accès</h2>
        <p class="text-black">Gérez les comptes de vos boutiquiers et magasiniers.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center">
        <i class="ri-user-add-line mr-2"></i> Ajouter un employé
    </a>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center">
        <i class="ri-checkbox-circle-line text-lg mr-2"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Nom d'utilisateur</th>
                    <th class="p-4 font-semibold">Rôle</th>
                    <th class="p-4 font-semibold">Salaire mensuel</th>
                    <th class="p-4 font-semibold">Shift</th>
                    <th class="p-4 font-semibold">Boutique</th>
                    <th class="p-4 font-semibold">Appareil Autorisé</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($users as $user)
                    <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4 font-medium text-slate-800">
                            {{ $user->nom_utilisateur }}
                            <div class="text-xs text-slate-500 font-normal">{{ $user->email }}</div>
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $user->role === 'magasinier' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="p-4 text-slate-700 font-medium">
                            {{ number_format($user->monthly_salary, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="p-4 text-slate-600">
                            <div>
                                @if($user->shift)
                                    <div class="mb-2">
                                        <i class="{{ $user->shift === 'matin' ? 'ri-sun-line text-amber-500' : 'ri-moon-line text-indigo-500' }} mr-1"></i>
                                        <span class="font-medium">{{ ucfirst($user->shift) }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 mb-2 block">-</span>
                                @endif
                                @if($user->horaires->count())
                                    <div class="text-xs text-slate-500 space-y-1">
                                        @foreach($user->horaires as $horaire)
                                            <div class="bg-slate-100 px-2 py-1 rounded">
                                                {{ $horaire->getDayLabel() }} : {{ substr($horaire->heure_debut, 0, 5) }} - {{ substr($horaire->heure_fin, 0, 5) }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="p-4 text-slate-600">
                            {{ $user->boutique ? $user->boutique->nom : '-' }}
                        </td>
                        <td class="p-4">
                            @if($user->device_token)
                                <span class="flex items-center text-emerald-600 text-xs font-medium">
                                    <i class="ri-mac-line mr-1 text-lg"></i> Autorisé
                                </span>
                            @else
                                <span class="flex items-center text-rose-500 text-xs font-medium">
                                    <i class="ri-error-warning-line mr-1 text-lg"></i> Non configuré
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition-colors" title="Modifier l'employé">
                                    <i class="ri-pencil-line"></i>
                                </a>

                                @if(!$user->device_token)
                                    <form action="{{ route('admin.users.authorize_device', $user) }}" method="POST" onsubmit="return confirm('Êtes-vous physiquement SUR l\'appareil de la boutique pour cet employé ? Si oui, cliquez sur OK pour autoriser ce navigateur de manière permanente.');">
                                        @csrf
                                        <button type="submit" class="p-2 bg-emerald-100 text-emerald-600 hover:bg-emerald-200 rounded-lg transition-colors" title="Autoriser cet appareil">
                                            <i class="ri-mac-fill"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.users.reset_device', $user) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment révoquer l\'accès de l\'ancien appareil de cet employé ?');">
                                        @csrf
                                        <button type="submit" class="p-2 bg-amber-100 text-amber-600 hover:bg-amber-200 rounded-lg transition-colors" title="Réinitialiser l'appareil">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Supprimer cet employé ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition-colors" title="Supprimer l'employé">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-500">
                            Aucun employé n'a encore été créé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
