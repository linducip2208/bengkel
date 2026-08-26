<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class TwoFactorController extends Controller
{
    public function showChallenge()
    {
        $userId = Session::get('2fa_pending_user');
        abort_unless($userId, 403);

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $validated = $request->validate(['code' => 'required|string|size:6']);
        $userId = Session::get('2fa_pending_user');
        $expected = Session::get('2fa_otp');
        $expiresAt = Session::get('2fa_expires_at');

        if (! $userId || ! $expected || ! $expiresAt || now()->timestamp > $expiresAt) {
            Session::forget(['2fa_pending_user', '2fa_otp', '2fa_expires_at', '2fa_remember']);

            return redirect()->route('login')->with('error', 'Kode kedaluwarsa, login ulang.');
        }

        if (! Hash::check($validated['code'], $expected)) {
            return back()->with('error', 'Kode OTP salah.');
        }

        $user = User::find($userId);
        Session::forget(['2fa_pending_user', '2fa_otp', '2fa_expires_at']);
        auth()->loginUsingId($user->id, (bool) Session::pull('2fa_remember'));
        $request->session()->regenerate();
        $user->update(['two_factor_verified_at' => now()]);
        ActivityLog::record('2fa.verified', $user, "User {$user->email} verifikasi 2FA sukses");

        return redirect()->intended(route('dashboard'));
    }

    public function enableForm()
    {
        return view('auth.two-factor-enable');
    }

    public function enable(Request $request)
    {
        $user = auth()->user();
        $user->update(['two_factor_enabled' => true]);
        ActivityLog::record('2fa.enabled', $user, "2FA enabled untuk {$user->email}");

        return back()->with('success', '2FA berhasil di-enable. Login berikutnya akan minta OTP via email.');
    }

    public function disable(Request $request)
    {
        $user = auth()->user();
        $user->update(['two_factor_enabled' => false]);
        ActivityLog::record('2fa.disabled', $user, "2FA disabled untuk {$user->email}");

        return back()->with('success', '2FA dinonaktifkan.');
    }

    public static function sendOtpAndSession(User $user): void
    {
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        Session::put('2fa_pending_user', $user->id);
        // Store only a hash — a leaked session dump must not reveal the code.
        Session::put('2fa_otp', Hash::make($otp));
        Session::put('2fa_expires_at', now()->addMinutes(10)->timestamp);

        if (! $user->email) {
            \Log::warning("2FA OTP not sent: user {$user->id} has no email address.");

            return;
        }

        try {
            Mail::raw("Kode OTP login Anda: {$otp}\n\nBerlaku 10 menit. Jangan share ke siapapun.", function ($m) use ($user) {
                $m->to($user->email)->subject('Kode OTP Login — '.config('app.name'));
            });
        } catch (\Throwable $e) {
            \Log::warning('2FA OTP send failed: '.$e->getMessage());
        }
    }
}
