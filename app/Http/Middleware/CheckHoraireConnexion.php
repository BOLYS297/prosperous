<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\HoraireConnexion;

class CheckHoraireConnexion
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier les tranches horaires seulement si l'utilisateur est connecté
        if (auth()->check()) {
            $user = auth()->user();

            // Les admins et super admins peuvent toujours accéder
            if (in_array($user->role, ['admin', 'super_admin'], true)) {
                return $next($request);
            }

            // Pour les employés (magasinier, boutiquier), vérifier les tranches horaires
            if (in_array($user->role, ['magasinier', 'boutiquier'], true)) {
                if (!HoraireConnexion::canUserConnect($user)) {
                    if ($request->expectsJson()) {
                        abort(403, 'Vous ne pouvez vous connecter que pendant les heures autorisées.');
                    }

                    auth()->logout();

                    return redirect()->route('login')
                        ->with('error', 'Vous ne pouvez vous connecter que pendant les heures autorisées. Veuillez vérifier votre planning.');
                }
            }
        }

        return $next($request);
    }
}
