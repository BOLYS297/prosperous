<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class RoleMiddleware
{
    /**
     * Vérifie si l'utilisateur authentifié a le rôle requis.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Si l'utilisateur n'est pas connecté, on le redirige vers la page de connexion
        if ((! Auth::check())) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
        }

        // Si le rôle ne correspond pas, on empêche l’accès
        if (Auth::user()->role !== ($role)) {
            abort(403, 'Accès refusé : rôle non autorisé.');
        }

        // Sinon on continue
        return $next($request);
    }
}
