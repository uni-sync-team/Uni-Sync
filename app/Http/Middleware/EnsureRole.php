<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Registered under two names in bootstrap/app.php:
     *   'active'            logged in + account still active
     *   'role:admin,prof'   same, and the user must hold at least one of these roles
     *
     * Use it after "auth":  ->middleware(['auth', 'role:admin'])
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not logged in: go to the login page, and come back here afterwards
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // An account deactivated while its owner is logged in is kicked out on the next request
        if (! $user->actif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['login' => 'Votre compte a été désactivé.']);
        }

        // Role check (skipped when no role is given, i.e. the 'active' alias)
        if ($roles !== [] && ! collect($roles)->contains(fn (string $role) => $user->hasRole($role))) {
            abort(403);
        }

        return $next($request);
    }
}
