<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Deactivated accounts must never authenticate.
        $credentials['is_active'] = true;

        // 2FA challenge: valid credentials + opted-in user → send OTP first.
        if (Auth::guard('web')->validate($credentials)) {
            $candidate = User::where('email', $credentials['email'])
                ->where('is_active', true)
                ->first();

            if ($candidate && $candidate->two_factor_enabled) {
                TwoFactorController::sendOtpAndSession($candidate);
                $request->session()->put('2fa_remember', $request->boolean('remember'));

                return redirect()->route('2fa.challenge')
                    ->with('info', 'Kode OTP telah dikirim via email. Berlaku 10 menit.');
            }
        }

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
