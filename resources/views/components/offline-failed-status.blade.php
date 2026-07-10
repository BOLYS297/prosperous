<div id="offline-failed-panel" class="hidden fixed bottom-44 right-4 z-50 w-[320px] max-w-[calc(100vw-2rem)] rounded-3xl border border-rose-200 bg-white/95 p-4 shadow-2xl backdrop-blur-xl transition-all duration-300">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-rose-500">Non synchronisées</p>
            <h4 id="offline-failed-title" class="text-sm font-semibold text-slate-900 mt-1">Aucune action refusée</h4>
        </div>
        <button id="offline-failed-clear" type="button" class="text-rose-600 hover:text-rose-800 text-xs font-semibold">Tout effacer</button>
    </div>
    <p class="mt-1 text-xs text-slate-500">Ces actions ont été refusées par le serveur (ex. stock épuisé entre-temps). Vérifiez et ressaisissez-les si nécessaire, puis effacez-les.</p>

    <div id="offline-failed-list" class="mt-3 max-h-64 overflow-y-auto space-y-2 text-sm text-slate-700"></div>
</div>
