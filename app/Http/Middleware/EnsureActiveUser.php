<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokir sesi/token milik user yang sudah dinonaktifkan (is_active=false).
 * Login sudah memfilter is_active, token lama dicabut saat penonaktifan,
 * tapi sesi web aktif / token Sanctum yang lolos di antaranya harus
 * ditolak per-request — bukan hanya saat login.
 */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_active === false) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akun dinonaktifkan.'], 401);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan.',
            ]);
        }

        return $next($request);
    }
}
