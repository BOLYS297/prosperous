@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Historique & Logs</h2>
    <p class="text-black">Trace d'audit et historique des actions effectuées sur la plateforme.</p>
</div>

<!-- Filtres -->
<div class="glass-panel p-6 rounded-2xl mb-8">
    <form action="{{ route('admin.logs.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-2">Utilisateur</label>
            <select name="user_id" class="w-full px-4 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">Tous les utilisateurs</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->nom_utilisateur }} ({{ ucfirst($user->role) }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-2">Date de l'action</label>
            <input type="date" name="date" value="{{ request('date') }}" class="w-full px-4 py-2 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="flex space-x-2">
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-colors flex items-center">
                <i class="ri-filter-3-line mr-2"></i> Filtrer
            </button>
            <a href="{{ route('admin.logs.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors flex items-center" title="Réinitialiser">
                <i class="ri-refresh-line"></i>
            </a>
        </div>
    </form>
</div>

<!-- Tableau des logs -->
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Date & Heure</th>
                    <th class="p-4 font-semibold">Utilisateur</th>
                    <th class="p-4 font-semibold">Rôle</th>
                    <th class="p-4 font-semibold">Action</th>
                    <th class="p-4 font-semibold">Description</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($logs as $log)
                    <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                        <td class="p-4 text-slate-500 font-mono text-xs">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="p-4 font-bold text-slate-800 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 font-bold">
                                {{ strtoupper(substr($log->user->nom_utilisateur ?? '?', 0, 1)) }}
                            </div>
                            {{ $log->user->nom_utilisateur ?? 'Utilisateur supprimé' }}
                        </td>
                        <td class="p-4">
                            @if($log->user)
                                <span class="px-2 py-1 rounded-full text-xs font-bold
                                    {{ $log->user->role == 'admin' ? 'bg-purple-100 text-purple-700' :
                                      ($log->user->role == 'magasinier' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ ucfirst($log->user->role) }}
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="p-4">
                            @php
                                $actionLabel = strtolower($log->action_label ?? $log->action);
                                $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                if(str_contains($actionLabel, 'validation') || str_contains($actionLabel, 'ajout') || str_contains($actionLabel, 'créa') || str_contains($actionLabel, 'enregistr')) $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                if(str_contains($actionLabel, 'rejet') || str_contains($actionLabel, 'erreur') || str_contains($actionLabel, 'suppression') || str_contains($actionLabel, 'déconnexion')) $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                if(str_contains($actionLabel, 'modif') || str_contains($actionLabel, 'mise à jour') || str_contains($actionLabel, 'gestion')) $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                if(str_contains($actionLabel, 'connexion')) $badgeClass = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            @endphp
                            <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold border {{ $badgeClass }}">
                                {{ $log->action_label ?? $log->action }}
                            </span>
                        </td>
                        <td class="p-4 text-slate-600">
                            {{ $log->description_label ?? $log->description }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-500">
                            <i class="ri-history-line text-4xl block mb-2"></i>
                            Aucun historique trouvé pour ces critères.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $logs->appends(request()->query())->links() }}
        </div>
    @endif
</div>
@endsection
