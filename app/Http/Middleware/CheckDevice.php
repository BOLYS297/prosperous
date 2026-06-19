<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Le super_admin peut se connecter de n'importe où
        if ($user && $user->role !== 'super_admin') {
            $cookieToken = $request->cookie('device_token');

            if (!$user->device_token) {
                Auth::logout();
                return redirect()->route('login')->withErrors(['email' => 'Cet utilisateur n\'a aucun appareil autorisé. Contactez l\'administrateur.']);
            }

            if (!$cookieToken || $cookieToken !== $user->device_token) {
                Auth::logout();
                return redirect()->route('login')->withErrors(['email' => 'Appareil non reconnu. Veuillez vous connecter sur l\'appareil autorisé de votre boutique.']);
            }
        }

        return $next($request);
    }
}
