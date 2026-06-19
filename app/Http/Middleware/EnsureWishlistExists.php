<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

class EnsureWishlistExists
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            // Rien à faire : wishlist liée à user
            return $next($request);
        }

        // Si visiteur sans wishlist en session, créer un identifiant unique de session-wishlist
        if (! session()->has('wishlist_session_id')) {
            session()->put('wishlist_session_id', Str::random(40));
        }

        return $next($request);
    }
}
