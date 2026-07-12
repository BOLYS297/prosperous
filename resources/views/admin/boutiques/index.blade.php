@extends('layouts.admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm mb-4 inline-flex items-center">
        <i class="ri-arrow-left-line mr-2"></i> Retour au tableau de bord
    </a>
    <h1 class="text-3xl font-bold text-slate-900 mb-3">Boutiques &amp; Trésorerie</h1>
    <p class="text-sm text-slate-600 max-w-2xl">Consultez le solde de chaque point de vente et enregistrez un versement de cash pour l'approvisionner. Les boutiquiers concernés sont notifiés du crédit.</p>
</div>

@if(session('success'))
    <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900 flex items-center">
        <i class="ri-check-line text-lg mr-2"></i>{{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-900 flex items-center">
        <i class="ri-error-warning-line text-lg mr-2"></i>{{ session('error') }}
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

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    @forelse($boutiques as $boutique)
        <div class="glass-panel rounded-2xl p-6 flex flex-col">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">{{ $boutique->nom }}</h3>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 mt-1 capitalize">
                        <i class="ri-{{ $boutique->type === 'magasin' ? 'archive' : 'store-2' }}-line mr-1"></i>{{ $boutique->type }}
                    </span>
                </div>
                <div class="w-11 h-11 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="ri-wallet-3-line text-xl text-blue-600"></i>
                </div>
            </div>

            <div class="mb-4">
                <p class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Solde actuel</p>
                <p class="text-2xl font-black {{ $boutique->solde < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                    {{ number_format($boutique->solde, 0, ',', ' ') }} {{ param("currency") }}
                </p>
            </div>

            @php $avanceEncours = (float) ($avancesEnCours[$boutique->id] ?? 0); @endphp
            @if($avanceEncours > 0)
                <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800 flex items-center">
                    <i class="ri-time-line mr-1.5 text-sm"></i> Avance en cours à rembourser : <strong class="ml-1">{{ number_format($avanceEncours, 0, ',', ' ') }} {{ param("currency") }}</strong>
                </div>
            @endif

            <a href="{{ route('admin.stocks.edit', $boutique) }}" class="mb-4 inline-flex items-center justify-center w-full px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-900 transition-colors font-medium">
                <i class="ri-archive-line mr-2"></i> Gérer le stock
            </a>

            <form action="{{ route('admin.boutiques.crediter', $boutique) }}" method="POST" class="mt-auto space-y-3 border-t border-slate-200 pt-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Type d'approvisionnement</label>
                    <div class="flex flex-col gap-1.5 text-sm text-slate-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="mode" value="simple" checked class="text-emerald-600 focus:ring-emerald-500">
                            <span>Simple <span class="text-slate-400">(sans remboursement)</span></span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="mode" value="dette" class="text-amber-600 focus:ring-amber-500">
                            <span>Avance <span class="text-slate-400">(à rembourser par la boutique)</span></span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Montant à créditer ({{ param("currency") }})</label>
                    <input type="number" name="montant" min="1" step="1" required placeholder="Ex : 50000"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Motif (facultatif)</label>
                    <input type="text" name="motif" maxlength="255" placeholder="Approvisionnement en cash"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" onclick="return confirm('Confirmer le crédit du solde de {{ $boutique->nom }} ?')"
                    class="w-full flex items-center justify-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium">
                    <i class="ri-add-circle-line mr-2"></i> Créditer le solde
                </button>
            </form>
        </div>
    @empty
        <div class="col-span-full glass-panel rounded-2xl p-6 text-center text-slate-500">
            Aucune boutique enregistrée.
        </div>
    @endforelse
</div>
@endsection
