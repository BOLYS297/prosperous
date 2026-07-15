@extends('layouts.magasinier')

@section('content')
<div class="mb-8 flex items-center">
    <a href="{{ route('magasinier.transferts-stock.index') }}" class="w-10 h-10 bg-white/50 rounded-full flex items-center justify-center text-blue-600 hover:bg-white transition-colors mr-4 shadow-sm">
        <i class="ri-arrow-left-line"></i>
    </a>
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Nouveau transfert entre points de vente</h2>
        <p class="text-black">Retirez du stock d'un point de vente pour l'envoyer vers un autre.</p>
    </div>
</div>

@if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm flex items-center">
        <i class="ri-error-warning-line text-lg mr-2"></i><span>{{ session('error') }}</span>
    </div>
@endif
@if($errors->any())
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="glass-panel rounded-2xl p-8 max-w-2xl bg-white">
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm flex">
        <i class="ri-information-line text-xl mr-3 shrink-0"></i>
        <p>Le vendeur du point de vente <strong>source</strong> devra autoriser la quantité à envoyer (le stock sortira à ce moment-là), puis le vendeur de <strong>destination</strong> confirmera la quantité reçue.</p>
    </div>

    <form action="{{ route('magasinier.transferts-stock.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Point de vente source <span class="text-red-500">*</span></label>
                <select name="source_boutique_id" id="source_boutique_id" required class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">-- Choisir --</option>
                    @foreach($boutiques as $b)
                        <option value="{{ $b->id }}" {{ old('source_boutique_id') == $b->id ? 'selected' : '' }}>{{ $b->nom }} ({{ $b->type }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Point de vente destination <span class="text-red-500">*</span></label>
                <select name="destination_boutique_id" id="destination_boutique_id" required class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">-- Choisir --</option>
                    @foreach($boutiques as $b)
                        <option value="{{ $b->id }}" {{ old('destination_boutique_id') == $b->id ? 'selected' : '' }}>{{ $b->nom }} ({{ $b->type }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">Produit <span class="text-red-500">*</span></label>
            <select name="produit_id" id="produit_id" required class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                <option value="">-- Choisir un produit --</option>
                @foreach($produits as $p)
                    <option value="{{ $p->id }}" {{ old('produit_id') == $p->id ? 'selected' : '' }}>{{ $p->nom }}@if($p->reference) ({{ $p->reference }})@endif</option>
                @endforeach
            </select>

            <div id="stock-info" class="hidden mt-3 flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-medium">
                <i class="ri-archive-line text-lg"></i>
                <span>Stock disponible à la source : <span id="stock-value" class="font-black">0</span></span>
            </div>
        </div>

        <div class="mb-2">
            <label class="block text-sm font-medium text-slate-700 mb-2">Quantité à transférer <span class="text-red-500">*</span></label>
            <input type="number" name="quantite_demandee" id="quantite_demandee" min="1" value="{{ old('quantite_demandee') }}" required placeholder="Ex: 20" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-500 outline-none text-2xl font-black text-slate-800">
        </div>

        <p id="stock-warning" class="hidden mb-6 text-sm font-semibold text-rose-600 flex items-center">
            <i class="ri-error-warning-line mr-1"></i><span id="stock-warning-text"></span>
        </p>
        <div id="stock-spacer" class="mb-6"></div>

        <button type="submit" id="submit-transfert" class="w-full px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg transition-all flex items-center justify-center text-lg disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="ri-arrow-left-right-line mr-2 text-xl"></i> Créer le transfert
        </button>
    </form>
</div>

<script>
    (function () {
        const STOCKS = @json($stocks);
        const source = document.getElementById('source_boutique_id');
        const destination = document.getElementById('destination_boutique_id');
        const produit = document.getElementById('produit_id');
        const qty = document.getElementById('quantite_demandee');
        const info = document.getElementById('stock-info');
        const value = document.getElementById('stock-value');
        const warning = document.getElementById('stock-warning');
        const warningText = document.getElementById('stock-warning-text');
        const spacer = document.getElementById('stock-spacer');
        const submit = document.getElementById('submit-transfert');

        function available() {
            const s = source.value, p = produit.value;
            if (!s || !p) return null;
            const parBoutique = STOCKS[s];
            if (!parBoutique) return 0;
            const v = parBoutique[p];
            return v === undefined || v === null ? 0 : parseInt(v, 10);
        }

        function styleInfo(avail) {
            info.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'bg-amber-50', 'text-amber-700', 'bg-rose-50', 'text-rose-700');
            if (avail <= 0) info.classList.add('bg-rose-50', 'text-rose-700');
            else if (avail <= 5) info.classList.add('bg-amber-50', 'text-amber-700');
            else info.classList.add('bg-emerald-50', 'text-emerald-700');
        }

        function refresh() {
            const avail = available();
            let blocked = false, msg = '';

            if (avail === null) {
                info.classList.add('hidden');
                qty.removeAttribute('max');
            } else {
                value.textContent = avail;
                styleInfo(avail);
                qty.max = avail > 0 ? avail : 1;

                const q = parseInt(qty.value, 10) || 0;
                if (avail <= 0) {
                    blocked = true;
                    msg = 'Ce produit est en rupture dans le point de vente source.';
                } else if (q > avail) {
                    blocked = true;
                    msg = 'La source ne dispose que de ' + avail + ' unité(s).';
                }
            }

            if (!blocked && source.value && destination.value && source.value === destination.value) {
                blocked = true;
                msg = 'La source et la destination doivent être différentes.';
            }

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

        [source, destination, produit].forEach(el => el.addEventListener('change', refresh));
        qty.addEventListener('input', refresh);
        refresh();
    })();
</script>
@endsection
