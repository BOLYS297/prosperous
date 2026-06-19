@extends('layouts.admin')

@section('content')
<div class="mb-8 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Fournisseurs & Grossistes</h2>
        <p class="text-black">Gérez vos partenaires commerciaux, vos fournisseurs et vos grossistes.</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.fournisseurs.create') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center">
            <i class="ri-truck-line mr-2"></i> Nouveau Fournisseur
        </a>
        <a href="{{ route('admin.grossistes.index') }}" class="px-5 py-2.5 bg-white text-blue-600 font-semibold rounded-xl shadow hover:bg-blue-50 transition-colors flex items-center">
            <i class="ri-store-line mr-2"></i> Gérer les Grossistes
        </a>
    </div>
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
                    <th class="p-4 font-semibold">Nom du Fournisseur</th>
                    <th class="p-4 font-semibold">Contact</th>
                    <th class="p-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($fournisseurs as $fournisseur)
                    <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4 font-bold text-slate-800">
                            {{ $fournisseur->nom }}
                        </td>
                        <td class="p-4 text-slate-600">
                            {{ $fournisseur->contact ?: '-' }}
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="{{ route('admin.fournisseurs.edit', $fournisseur) }}" class="p-2 bg-blue-100 text-blue-600 hover:bg-blue-200 rounded-lg transition-colors" title="Modifier">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <form action="{{ route('admin.fournisseurs.destroy', $fournisseur) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition-colors" title="Supprimer">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <i class="ri-truck-line text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-slate-800">Aucun fournisseur</h3>
                            <p class="text-slate-500 mt-1">Vous n'avez pas encore enregistré de fournisseur.</p>
                            <a href="{{ route('admin.fournisseurs.create') }}" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">Ajouter un fournisseur</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
