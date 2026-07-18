<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enregistre la « dernière présence » de l'employé connecté.
 *
 * Throttlé : on n'écrit qu'au plus une fois par minute, et via une écriture
 * directe (pas d'événement de modèle, pas de updated_at) pour rester léger. Ce
 * signal sert à détecter un départ anticipé quand l'employé ferme l'application
 * sans se déconnecter (voir LoginController::rattraperDepartManque).
 */
class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $last = $user->last_seen_at ? Carbon::parse($user->last_seen_at) : null;

            if (! $last || $last->diffInSeconds(now()) >= 60) {
                DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
            }
        }

        return $next($request);
    }
}
