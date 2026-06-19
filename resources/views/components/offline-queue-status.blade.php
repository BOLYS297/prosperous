<div id="offline-queue-panel" class="hidden fixed bottom-24 right-4 z-50 w-[320px] max-w-[calc(100vw-2rem)] rounded-3xl border border-slate-200 bg-white/95 p-4 shadow-2xl backdrop-blur-xl transition-all duration-300">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Actions hors-ligne</p>
            <h4 id="offline-queue-title" class="text-sm font-semibold text-slate-900 mt-1">Aucune action en attente</h4>
        </div>
        <button id="offline-queue-toggle" type="button" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">Détails</button>
    </div>
    <p id="offline-queue-summary" class="mt-2 text-xs text-slate-500">Quand vous êtes hors ligne, les ventes et demandes sont mises en attente.</p>

    <div id="offline-queue-list" class="mt-4 hidden space-y-3 text-sm text-slate-700"></div>
</div>
