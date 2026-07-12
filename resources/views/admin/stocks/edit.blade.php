@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.boutiques.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm mb-4 inline-flex items-center">
        <i class="ri-arrow-left-line mr-2"></i> Retour aux boutiques
    </a>
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Stock — {{ $boutique->nom }}</h1>
    <p class="text-sm text-slate-600 max-w-2xl">
        Ajustez manuellement la quantité en stock de chaque produit pour ce point de vente.
        Saisissez la <strong>nouvelle quantité totale</strong> : une augmentation crée un lot, une diminution retire du stock (FIFO).
    </p>
    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 mt-2 capitalize">
        <i class="ri-{{ $boutique->type === 'magasin' ? 'archive' : 'store-2' }}-line mr-1"></i>{{ $boutique->type }}
    </span>
</div>

@if(session('success'))
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900 flex items-center">
        <i class="ri-check-line text-lg mr-2"></i>{{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-900">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-4">
    <input type="text" id="stock-search" placeholder="Rechercher un produit (nom ou référence)..."
        class="w-full md:w-1/2 px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
        autocomplete="off">
</div>

<form action="{{ route('admin.stocks.update', $boutique) }}" method="POST" class="glass-panel rounded-2xl p-6">
    @csrf
    @method('PUT')

    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-slate-50 border-b border-slate-200 text-sm text-slate-600">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Produit</th>
                    <th class="px-4 py-3 text-center font-semibold">Stock actuel</th>
                    <th class="px-4 py-3 text-left font-semibold">Nouvelle quantité</th>
                </tr>
            </thead>
            <tbody id="stock-tbody" class="divide-y divide-slate-100">
                @foreach($produits as $produit)
                    @php $actuel = (int) ($stockActuel[$produit->id] ?? 0); @endphp
                    <tr data-search="{{ \Illuminate\Support\Str::lower(trim($produit->nom . ' ' . $produit->reference)) }}">
                        <td class="px-4 py-3 font-semibold text-slate-800">
                            {{ $produit->nom }}@if($produit->reference) <span class="text-xs text-slate-400 font-mono">({{ $produit->reference }})</span>@endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold {{ $actuel <= 0 ? 'bg-rose-100 text-rose-700' : ($actuel <= 5 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                {{ $actuel }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <input type="hidden" name="stocks[{{ $loop->index }}][produit_id]" value="{{ $produit->id }}">
                            <input type="number" name="stocks[{{ $loop->index }}][quantite]" min="0" step="1"
                                value="{{ $actuel }}" data-initial="{{ $actuel }}"
                                class="stock-qty w-32 px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p id="stock-empty" class="hidden text-center text-slate-500 py-6">Aucun produit ne correspond à la recherche.</p>
    </div>

    <div class="mt-6 flex items-center gap-4">
        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center">
            <i class="ri-save-line mr-2"></i> Enregistrer les modifications
        </button>
        <span id="stock-changed-count" class="text-sm text-slate-500"></span>
    </div>
</form>

<script>
    (function () {
        const search = document.getElementById('stock-search');
        const rows = Array.from(document.querySelectorAll('#stock-tbody tr'));
        const empty = document.getElementById('stock-empty');
        const inputs = Array.from(document.querySelectorAll('.stock-qty'));
        const changedLabel = document.getElementById('stock-changed-count');

        if (search) {
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
        }

        function updateChanged() {
            let changed = 0;
            inputs.forEach(function (input) {
                const initial = parseInt(input.dataset.initial, 10);
                const val = parseInt(input.value, 10);
                if (!isNaN(val) && val !== initial) {
                    changed++;
                    input.classList.add('border-blue-500', 'bg-blue-50');
                } else {
                    input.classList.remove('border-blue-500', 'bg-blue-50');
                }
            });
            changedLabel.textContent = changed > 0 ? (changed + ' produit(s) modifié(s)') : '';
        }

        inputs.forEach(function (input) {
            input.addEventListener('input', updateChanged);
        });
    })();
</script>
@endsection
