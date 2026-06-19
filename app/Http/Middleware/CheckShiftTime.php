<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckShiftTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->role === 'boutiquier' && $user->shift) {
            $currentHour = (int) now()->setTimezone(date_default_timezone_get())->format('H');

            if ($user->shift === 'matin') {
                if ($currentHour < 7 || $currentHour >= 17) {
                    Auth::logout();
                    return redirect()->route('login')->withErrors(['email' => 'Vous ne pouvez vous connecter qu\'entre 7h et 17h.']);
                }
            } elseif ($user->shift === 'soir') {
                if ($currentHour < 17 || $currentHour >= 22) {
                    Auth::logout();
                    return redirect()->route('login')->withErrors(['email' => 'Vous ne pouvez vous connecter qu\'entre 17h et 22h.']);
                }
            }
        }

        return $next($request);
    }
}
