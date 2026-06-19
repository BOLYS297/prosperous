@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Grossistes</h1>
        <a href="{{ route('admin.grossistes.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Ajouter un Grossiste
        </a>
    </div>

    @if ($message = Session::get('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ $message }}
        </div>
    @endif

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full">
            <thead class="bg-gray-100 border-b">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Nb Produits</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($grossistes as $grossiste)
                    <tr>
                        <td class="px-6 py-4 font-semibold">{{ $grossiste->nom }}</td>
                        <td class="px-6 py-4">
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono">{{ $grossiste->code }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $grossiste->contact ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">{{ $grossiste->prixProduits->count() }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('admin.grossistes.pricing', $grossiste) }}" class="bg-purple-600 hover:bg-purple-700 text-black font-bold py-1 px-3 rounded text-sm">
                                Tarifs
                            </a>
                            <a href="{{ route('admin.grossistes.edit', $grossiste) }}" class="bg-yellow-600 hover:bg-yellow-700 text-black font-bold py-1 px-3 rounded text-sm">
                                Modifier
                            </a>
                            <form method="POST" action="{{ route('admin.grossistes.destroy', $grossiste) }}" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm" onclick="return confirm('Êtes-vous sûr?')">
                                    Supprimer
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Aucun grossiste trouvé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $grossistes->links() }}
    </div>
</div>
@endsection
