<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Replaces User::verifyCredentials(): checks the password hash AND that the
        // account is active ("actif" => true is added to the WHERE of the user lookup).
        if (! Auth::attempt([...$credentials, 'actif' => true])) {
            // one generic message: never say whether the login or the password was wrong
            return back()
                ->withErrors(['login' => 'Login ou mot de passe incorrect, ou compte désactivé.'])
                ->onlyInput('login');
        }

        // protects against session fixation
        $request->session()->regenerate();

        return redirect()->intended(route('space'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
