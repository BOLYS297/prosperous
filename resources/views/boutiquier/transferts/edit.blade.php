@extends('layouts.boutiquier')

@section('content')
<div class="mb-8 flex items-center">
    <a href="{{ route('boutiquier.transferts.index') }}" class="w-10 h-10 bg-white/50 rounded-full flex items-center justify-center text-blue-600 hover:bg-white transition-colors mr-4 shadow-sm">
        <i class="ri-arrow-left-line"></i>
    </a>
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Modifier la demande de stock</h2>
        <p class="text-black">Demande créée le {{ $demande->created_at->format('d/m/Y à H:i') }}</p>
    </div>
</div>

@if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
        <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 text-sm flex items-start">
    <i class="ri-information-line text-lg mr-2 mt-0.5"></i>
    <p>Le magasin n'a pas encore traité cette demande : vous pouvez encore la modifier. Dès qu'elle est expédiée ou refusée, elle est verrouillée.</p>
</div>

<div class="glass-panel rounded-2xl p-8 max-w-xl">
    <form action="{{ route('boutiquier.transferts.update', $demande->id) }}" method="POST" data-offline-sync="true">
        @csrf
        @method('PUT')

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Produit <span class="text-red-500">*</span></label>
            <select name="produit_id" id="produit_id" required class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none">
                @foreach($produits as $p)
                    <option value="{{ $p->id }}" {{ (int) old('produit_id', $demande->produit_id) === (int) $p->id ? 'selected' : '' }}>
                        {{ $p->nom }}@if($p->reference) ({{ $p->reference }})@endif
                    </option>
                @endforeach
            </select>

            <div id="stock-magasin-info" class="hidden mt-3 flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium">
                <i class="ri-store-2-line text-lg"></i>
                <span>Stock disponible au magasin : <span id="stock-magasin-value" class="font-black">0</span></span>
            </div>
        </div>

        <div class="mb-2">
            <label class="block text-sm font-medium text-slate-700 mb-2">Quantité demandée <span class="text-red-500">*</span></label>
            <input type="number" id="quantite_demandee" name="quantite_demandee" min="1" value="{{ old('quantite_demandee', $demande->quantite_demandee) }}" required
                class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none text-2xl font-black text-slate-800">
        </div>

        <p id="stock-warning" class="hidden mb-6 text-sm font-semibold text-rose-600 flex items-center">
            <i class="ri-error-warning-line mr-1"></i><span id="stock-warning-text"></span>
        </p>
        <div id="stock-spacer" class="mb-6"></div>

        <div class="flex gap-3">
            <button type="submit" id="submit-demande" class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="ri-save-line mr-2"></i> Enregistrer
            </button>
            <a href="{{ route('boutiquier.transferts.index') }}" class="px-6 py-3 bg-slate-200 text-slate-800 rounded-xl hover:bg-slate-300 transition-colors font-medium flex items-center">Annuler</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const STOCK_MAGASIN = @json($stockMagasin);
        const produit = document.getElementById('produit_id');
        const qty = document.getElementById('quantite_demandee');
        const info = document.getElementById('stock-magasin-info');
        const value = document.getElementById('stock-magasin-value');
        const warning = document.getElementById('stock-warning');
        const warningText = document.getElementById('stock-warning-text');
        const spacer = document.getElementById('stock-spacer');
        const submit = document.getElementById('submit-demande');

        function available() {
            const pid = produit.value;
            if (!pid) return null;
            const v = STOCK_MAGASIN[pid];
            return v === undefined || v === null ? 0 : parseInt(v, 10);
        }

        function refresh() {
            const avail = available();
            if (avail === null) { info.classList.add('hidden'); return; }

            value.textContent = avail;
            info.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'bg-amber-50', 'text-amber-700', 'bg-rose-50', 'text-rose-700');
            if (avail <= 0) info.classList.add('bg-rose-50', 'text-rose-700');
            else if (avail <= 5) info.classList.add('bg-amber-50', 'text-amber-700');
            else info.classList.add('bg-emerald-50', 'text-emerald-700');

            qty.max = avail > 0 ? avail : 1;
            const q = parseInt(qty.value, 10) || 0;
            let blocked = false, msg = '';

            if (avail <= 0) { blocked = true; msg = 'Ce produit est en rupture au magasin.'; }
            else if (q > avail) { blocked = true; msg = 'Le magasin ne dispose que de ' + avail + ' unité(s).'; }

            submit.disabled = blocked;
            if (blocked) {
                warningText.textContent = msg;
                warning.classList.remove('hidden');
                spacer.classList.add('hidden');
            } else {
                warning.classList.add('hidden');
                spacer.classList.remove('hidden');
            }
        }

        produit.addEventListener('change', refresh);
        qty.addEventListener('input', refresh);
        refresh();
    })();
</script>
@endsection
