@php
$shiftCountdownSeconds = null;
$user = Auth::user();
if ($user && in_array($user->role, ['magasinier', 'boutiquier'], true)) {
    $interval = \App\Models\HoraireConnexion::getCurrentIntervalForUser($user);
    if ($interval) {
            // Construire un Carbon fiable pour la fin de tranche en combinant la date d'aujourd'hui
            // et la valeur stockée (qui peut être "HH:MM" ou "HH:MM:SS").
            try {
                $endTime = \Illuminate\Support\Carbon::parse(now()->toDateString() . ' ' . $interval->heure_fin);
            } catch (\Exception $e) {
                $endTime = \Illuminate\Support\Carbon::today()->setTimeFromTimeString($interval->heure_fin);
            }

            $now = now();
            $remaining = $endTime->getTimestamp() - $now->getTimestamp();

            if ($remaining > 0 && $remaining <= 1800) {
                $shiftCountdownSeconds = $remaining;
            }
        }
}
@endphp

@if($shiftCountdownSeconds)
    <div x-data="{ remaining: {{ $shiftCountdownSeconds }}, minutes: '00', seconds: '00', timer: null, init() { this.updateTime(); this.timer = setInterval(() => this.updateTime(), 1000); }, updateTime() { if (this.remaining <= 0) { clearInterval(this.timer); this.remaining = 0; } this.minutes = String(Math.floor(this.remaining / 60)).padStart(2, '0'); this.seconds = String(this.remaining % 60).padStart(2, '0'); this.remaining -= 1; } }"
         x-init="init()"
         class="mb-6 rounded-2xl bg-amber-100 border border-amber-200 p-4 shadow-sm text-amber-900">
        <div class="flex items-start gap-3">
            <div class="mt-0.5">
                <i class="ri-time-line text-3xl"></i>
            </div>
            <div>
                <h2 class="font-semibold text-lg">Fin de votre tranche horaire</h2>
                <p class="text-sm text-amber-700 mt-1">Votre session prendra fin automatiquement dans <strong x-text="minutes + ':' + seconds"></strong>.</p>
                <p class="text-xs text-amber-700 mt-1">Pensez à enregistrer votre travail et à clôturer vos opérations avant la fin de votre tranche.</p>
            </div>
        </div>
    </div>
@endif
