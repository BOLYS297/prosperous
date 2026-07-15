@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-2">
        <h1 class="text-3xl font-bold">Tarifs — {{ $grossiste->nom }}</h1>
        <a href="{{ route('admin.grossistes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Retour
        </a>
    </div>
    <p class="text-sm text-slate-500 mb-6">Chaque produit est pré-rempli avec son <strong>prix grossiste par défaut</strong>. Modifiez uniquement ceux à personnaliser pour ce grossiste, puis enregistrez.</p>

    @if ($message = Session::get('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ $message }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4">
        <input type="text" id="tarif-search" placeholder="Rechercher un produit (nom ou référence)..."
            class="w-full md:w-1/2 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"
            autocomplete="off">
    </div>

    <form action="{{ route('admin.grossistes.pricing.update', $grossiste) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Produit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Prix Achat ({{ param("currency") }})</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Prix Vente Grossiste ({{ param("currency") }})</th>
                    </tr>
                </thead>
                <tbody class="divide-y" id="tarif-tbody">
                    @foreach ($produits as $produit)
                        @php
                            $existant = $existants[$produit->id] ?? null;
                            $defautVente = $defauts[$produit->id] ?? 0;
                            $prixAchat = $existant?->prix_achat ?? $produit->getRawOriginal('prix_achat');
                            $prixVente = $existant?->prix_vente ?? $defautVente;
                            // Personnalisé = tarif enregistré différent du prix par défaut.
                            $estPersonnalise = $existant && abs((float) $existant->prix_vente - (float) $defautVente) > 0.001;
                        @endphp
                        <tr data-search="{{ \Illuminate\Support\Str::lower(trim($produit->nom . ' ' . $produit->reference)) }}">
                            <td class="px-6 py-4 font-semibold">
                                {{ $produit->nom }}@if($produit->reference) <span class="text-xs text-slate-400 font-mono">({{ $produit->reference }})</span>@endif
                                @if($estPersonnalise)
                                    <span class="ml-2 inline-block text-[10px] font-bold text-purple-700 bg-purple-100 px-2 py-0.5 rounded-full">personnalisé</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <input type="hidden" name="prix[{{ $loop->index }}][produit_id]" value="{{ $produit->id }}">
                                <input type="number" name="prix[{{ $loop->index }}][prix_achat]" step="0.01" min="0" class="tarif-input w-32 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" value="{{ $prixAchat }}" data-initial="{{ $prixAchat }}">
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="prix[{{ $loop->index }}][prix_vente]" step="0.01" min="0" class="tarif-input w-32 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600" value="{{ $prixVente }}" data-initial="{{ $prixVente }}" required>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p id="tarif-empty" class="hidden text-center text-gray-500 py-6">Aucun produit ne correspond à la recherche.</p>
        </div>

        <div class="mt-6 flex gap-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Enregistrer les Tarifs
            </button>
            <a href="{{ route('admin.grossistes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Annuler
            </a>
        </div>
    </form>
</div>

<script>
    (function () {
        const search = document.getElementById('tarif-search');
        const rows = Array.from(document.querySelectorAll('#tarif-tbody tr'));
        const empty = document.getElementById('tarif-empty');
        if (!search) return;

        search.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            let visible = 0;
            rows.forEach(function (row) {
                const match = !q || (row.dataset.search || '').includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            empty.classList.toggle('hidden', visible !== 0);
        });

        // On n'envoie QUE les lignes dont un prix a changé : envoyer les 544
        // produits (x3 champs) dépasserait max_input_vars et PHP tronquerait
        // silencieusement le formulaire (tarifs perdus).
        const form = document.querySelector('form[action*="pricing"]');
        if (form) {
            form.addEventListener('submit', function (e) {
                let changed = 0;

                rows.forEach(function (row) {
                    const champs = Array.from(row.querySelectorAll('.tarif-input'));
                    const modifie = champs.some(function (i) {
                        return String(i.value).trim() !== String(i.dataset.initial).trim();
                    });

                    if (modifie) {
                        changed++;
                    } else {
                        champs.forEach(function (i) { i.disabled = true; });
                        const hidden = row.querySelector('input[name$="[produit_id]"]');
                        if (hidden) hidden.disabled = true;
                    }
                });

                if (changed === 0) {
                    e.preventDefault();
                    rows.forEach(function (row) {
                        row.querySelectorAll('input').forEach(function (i) { i.disabled = false; });
                    });
                    alert('Aucun tarif modifié : rien à enregistrer.');
                }
            });
        }
    })();
</script>
@endsection
