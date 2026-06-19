@extends('layouts.boutiquier')

@section('content')
<div class="mb-8 flex items-center">
    <a href="{{ route('boutiquier.transferts.index') }}" class="w-10 h-10 bg-white/50 rounded-full flex items-center justify-center text-blue-600 hover:bg-white hover:text-blue-800 transition-colors mr-4 shadow-sm">
        <i class="ri-arrow-left-line"></i>
    </a>
    <div>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Nouvelle Demande de Stock</h2>
        <p class="text-black">Sollicitez le magasin central pour réapprovisionner votre boutique.</p>
    </div>
</div>

<div class="glass-panel rounded-2xl p-8 max-w-xl">

    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-700 text-sm flex">
        <i class="ri-information-line text-xl mr-3 flex-shrink-0"></i>
        <p>Une fois la demande envoyée, le magasinier devra la valider et expédier les produits. Le stock ne sera ajouté à votre boutique qu'une fois que vous aurez <strong>confirmé la réception</strong>.</p>
    </div>

    <form action="{{ route('boutiquier.transferts.store') }}" method="POST" data-offline-sync="true">
        @csrf

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-600 text-sm">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Produit à réapprovisionner <span class="text-red-500">*</span></label>
            <x-produit-search
                id="produit_transfert"
                fieldName="produit_id"
                placeholder="Rechercher un produit..."
                :produits="$produits"
            />
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-2">Quantité demandée <span class="text-red-500">*</span></label>
            <input type="number" name="quantite_demandee" min="1" class="w-full px-4 py-3 border border-slate-300 rounded-xl bg-white/50 focus:ring-2 focus:ring-blue-500 outline-none text-2xl font-black text-slate-800" placeholder="Ex: 50" required>
        </div>

        <button type="submit" class="w-full px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center justify-center text-lg">
            <i class="ri-send-plane-fill mr-2 text-xl"></i> Envoyer la Demande
        </button>
    </form>
</div>
@endsection
