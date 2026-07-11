@extends('layouts.boutiquier')

@section('content')
<div class="mb-8 flex justify-between items-center">
    <div>
        <a href="{{ route('boutiquier.ventes.historique') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
            <i class="ri-arrow-left-line mr-1"></i> Retour à l'historique
        </a>
        <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Ticket de caisse #{{ str_pad($vente->id, 4, '0', STR_PAD_LEFT) }}</h2>
        <p class="text-sm text-slate-500">{{ $vente->created_at->format('d/m/Y à H:i') }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('boutiquier.ventes.edit', $vente) }}" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors flex items-center">
            <i class="ri-edit-line mr-2"></i> Modifier
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors flex items-center">
            <i class="ri-printer-line mr-2"></i> Imprimer
        </button>
        <form action="{{ route('boutiquier.ventes.destroy', $vente) }}" method="POST" style="display: inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors flex items-center" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette vente ?');">
                <i class="ri-delete-bin-line mr-2"></i> Supprimer
            </button>
        </form>
    </div>
</div>

<div class="receipt bg-white shadow rounded-2xl p-5 mx-auto">
    <div class="text-center mb-4">
        <div class="font-bold text-lg text-slate-900">{{ param('company_name') }}</div>
        <div class="text-xs text-slate-500">Ticket de caisse</div>
        @if(param('company_immatriculation'))
            <div class="text-xs text-slate-500">Immatriculation : {{ param('company_immatriculation') }}</div>
        @endif
        @if(param('company_address'))
            <div class="text-xs text-slate-500">{{ param('company_address') }}</div>
        @endif
        @if(param('company_phone'))
            <div class="text-xs text-slate-500">Tél : {{ param('company_phone') }}</div>
        @endif
    </div>

    <div class="text-xs text-slate-600 mb-4">
        <div>Vente #: {{ str_pad($vente->id, 4, '0', STR_PAD_LEFT) }}</div>
        <div>Boutiquier: {{ $vente->user->nom_utilisateur ?? $vente->user->name ?? 'N/A' }}</div>
        <div>Date: {{ $vente->created_at->format('d/m/Y') }}</div>
        <div>Heure: {{ $vente->created_at->format('H:i') }}</div>
    </div>

    <div class="border-t border-slate-200 pt-3">
        @foreach($vente->lignes as $ligne)
            <div class="flex justify-between items-center mb-3 text-xs text-slate-800">
                <div>
                    <div class="font-semibold">{{ $ligne->produit->nom ?? 'Produit', 18 }}</div>
                    @if($ligne->produit && $ligne->produit->reference)
                        <div class="text-slate-600 font-mono text-xs bg-slate-100 inline-block px-2 py-1 rounded mt-1">{{ $ligne->produit->reference }}</div>
                    @endif
                    <div class="text-slate-500 mt-1">{{ $ligne->quantite }} x {{ number_format($ligne->prix_unitaire, 0, ',', ' ') }} {{ param('currency') }}</div>
                </div>
                <div class="text-right font-bold">
                    {{ number_format($ligne->quantite * $ligne->prix_unitaire, 0, ',', ' ') }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="border-t border-slate-200 mt-4 pt-3 text-xs text-slate-600">
        <div class="flex justify-between mb-1">
            <span>Total</span>
            <span class="font-bold text-slate-900">{{ number_format($vente->montant_total, 0, ',', ' ') }} {{ param('currency') }}</span>
        </div>
    </div>

    <div class="text-center text-[10px] text-slate-500 mt-5">
        {{ param('ticket_footer', 'Merci de votre visite !') }}
    </div>
</div>

<style>
    .receipt {
        width: 320px;
        max-width: 100%;
    }

    @media print {
        @page {
            size: auto;
            margin: 0.5cm;
        }

        html, body {
            width: auto;
            height: auto;
            margin: 0;
            padding: 0;
            background: transparent;
        }

        body * {
            visibility: hidden;
        }

        .receipt,
        .receipt * {
            visibility: visible;
        }

        .receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            max-width: 340px;
            padding: 0.5cm;
            box-shadow: none;
            border-radius: 0;
        }

        .receipt * {
            color: #000 !important;
        }

        button,
        a {
            display: none !important;
        }
    }
</style>
@endsection
