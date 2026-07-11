@extends('layouts.boutiquier')

@section('content')
<div class="mb-8">
    <a href="{{ route('boutiquier.dashboard') }}" class="text-blue-200 hover:text-white transition-colors flex items-center text-sm mb-4">
        <i class="ri-arrow-left-line mr-1"></i> Retour à la caisse
    </a>
    <h2 class="text-3xl font-bold text-primary mb-2 tracking-tight">Historique des Ventes de la Semaine</h2>
    <p class="text-black">Du {{ $debutSemaine->translatedFormat('d F') }} au {{ $finSemaine->translatedFormat('d F Y') }} — Total : <span class="font-bold">{{ number_format($totalSemaine, 0, ',', ' ') }} {{ param("currency") }}</span></p>
</div>

{{-- <!-- Total du jour -->
<div class="glass-panel p-6 rounded-2xl mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <div class="p-4 bg-emerald-100 text-emerald-600 rounded-xl mr-4 shadow-sm border border-emerald-200">
            <i class="ri-wallet-3-line text-3xl"></i>
        </div>
        <div>
            <div class="text-sm font-medium text-slate-500">Total des recettes du jour</div>
            <div class="text-3xl font-black text-slate-800">{{ number_format($totalJour, 0, ',', ' ') }} <span class="text-sm font-medium text-slate-500">{{ param("currency") }}</span></div>
        </div>
    </div>
    <div class="text-5xl font-black text-emerald-200">
        <i class="ri-line-chart-line"></i>
    </div>
</div> --}}

<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-6 bg-white/50 border-b border-slate-200/50">
        <h3 class="font-bold text-slate-800">Détail des ventes</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white/40 border-b border-white/50 text-sm text-slate-600">
                    <th class="p-4 font-semibold">Date &amp; heure</th>
                    <th class="p-4 font-semibold">Produit(s)</th>
                    <th class="p-4 font-semibold text-center">Qté</th>
                    <th class="p-4 font-semibold text-right">Montant</th>
                    <th class="p-4 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="historique-tbody" class="text-sm">
                {{-- Contenu rendu côté serveur si présent; sera remplacé en hors-ligne --}}
                @forelse($ventes as $vente)
                    @foreach($vente->lignes as $ligne)
                    <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                        <td class="p-4 text-slate-500">
                            <i class="ri-time-line mr-1"></i>{{ $vente->created_at->translatedFormat('D d/m') }} à {{ $vente->created_at->format('H:i') }}
                        </td>
                        <td class="p-4 font-bold text-slate-800">
                            {{ $ligne->produit->nom ?? '—' }}
                            @if($ligne->produit && $ligne->produit->reference)
                                <div class="text-xs text-slate-500 font-mono mt-1">{{ $ligne->produit->reference }}</div>
                            @endif
                        </td>
                        <td class="p-4 text-center font-bold text-slate-700">
                            {{ $ligne->quantite }}
                        </td>
                        <td class="p-4 text-right font-black text-blue-600">
                            {{ number_format($ligne->quantite * $ligne->prix_unitaire, 0, ',', ' ') }} {{ param("currency") }}
                        </td>
                        @if($loop->first)
                            @php $modifiable = $vente->created_at->copy()->addHours(24)->isFuture(); @endphp
                            <td class="p-4 text-center align-top" rowspan="{{ $vente->lignes->count() }}">
                                <div class="flex flex-col items-stretch gap-1.5">
                                    <a href="{{ route('boutiquier.ventes.show', $vente) }}" class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="ri-file-list-3-line mr-1"></i> Ticket
                                    </a>
                                    @if($modifiable)
                                        <a href="{{ route('boutiquier.ventes.edit', $vente) }}" class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition-colors">
                                            <i class="ri-edit-line mr-1"></i> Modifier
                                        </a>
                                        <form action="{{ route('boutiquier.ventes.destroy', $vente) }}" method="POST" onsubmit="return confirm('Supprimer définitivement cette vente ? Le stock sera restauré.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-white bg-rose-600 rounded-lg hover:bg-rose-700 transition-colors">
                                                <i class="ri-delete-bin-line mr-1"></i> Supprimer
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-slate-400 italic">Verrouillée (&gt; 24h)</span>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-500">
                            <i class="ri-inbox-line text-4xl block mb-2"></i>
                            Aucune vente enregistrée cette semaine.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
    (function () {
        const tbody = document.getElementById('historique-tbody');
        if (!tbody) return;

        function renderRows(ventes) {
            if (!ventes || ventes.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-500">
                            <i class="ri-inbox-line text-4xl block mb-2"></i>
                            Aucune vente enregistrée aujourd'hui.
                        </td>
                    </tr>
                `;
                return;
            }

            const rows = [];
            ventes.forEach((v) => {
                const createdAt = new Date(v.created_at);
                const timeLabel = createdAt.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
                v.lignes.forEach((ligne, idx) => {
                    const montant = (ligne.quantite * ligne.prix_unitaire) || 0;
                    rows.push(`
                        <tr class="border-b border-white/20 hover:bg-white/30 transition-colors">
                            <td class="p-4 text-slate-500"> <i class="ri-time-line mr-1"></i> ${timeLabel} </td>
                            <td class="p-4 font-bold text-slate-800"> ${ligne.produit?.nom || '—'} </td>
                            <td class="p-4 text-center font-bold text-slate-700"> ${ligne.quantite} </td>
                            <td class="p-4 text-right font-black text-blue-600"> ${new Intl.NumberFormat('fr-FR').format(montant)} {{ param("currency") }} </td>
                            ${idx === 0 ? `<td class="p-4 text-center" rowspan="${v.lignes.length}"><a href="/boutiquier/ventes/${v.id}" class="inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">Actions</a></td>` : ''}
                        </tr>
                    `);
                });
            });

            tbody.innerHTML = rows.join('\n');
        }

        async function tryRenderFromIndexedDb() {
            if (!window.Idb || !window.Idb.getSalesByDate) {
                // wait briefly for bundled scripts to initialize
                setTimeout(tryRenderFromIndexedDb, 200);
                return;
            }

            if (navigator.onLine) return; // prefer server-rendered when online

            // date string YYYY-MM-DD from server time
            const today = '{{ now()->toDateString() }}';
            const ventes = await window.Idb.getSalesByDate(today);
            renderRows(ventes);
        }

        document.addEventListener('DOMContentLoaded', tryRenderFromIndexedDb);
    })();
</script>
@endsection
