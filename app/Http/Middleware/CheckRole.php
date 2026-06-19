<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $userRole = trim(strtolower(Auth::user()->role));
        $allowedRoles = array_map(fn($role) => trim(strtolower($role)), $roles);

        // Un super_admin a tous les droits
        if ($userRole === 'super_admin') {
            return $next($request);
        }

        if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
            return abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
