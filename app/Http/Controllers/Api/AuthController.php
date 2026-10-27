<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->where('is_active', true)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Akun 2FA tidak boleh dapat token hanya dengan password —
        // terbitkan challenge OTP dulu (tanpa token).
        if ($user->two_factor_enabled) {
            return $this->issueOtpChallenge($user);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
        ]);
    }

    /**
     * Verifikasi OTP untuk login 2FA via API.
     * Challenge kedaluwarsa 10 menit; kode salah menahan challenge
     * (rate-limit route yang menahan brute-force).
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $challenge = Cache::get('2fa_challenge:'.$request->input('challenge_token'));

        if (! is_array($challenge) || empty($challenge['user_id']) || empty($challenge['otp_hash'])) {
            return response()->json(['message' => 'Challenge kedaluwarsa, login ulang.'], 401);
        }

        if (! Hash::check($request->input('code'), $challenge['otp_hash'])) {
            return response()->json(['message' => 'Kode OTP salah.'], 401);
        }

        Cache::forget('2fa_challenge:'.$request->input('challenge_token'));

        $user = User::where('id', $challenge['user_id'])->where('is_active', true)->first();
        abort_unless($user instanceof User, 401, 'Invalid credentials');

        $user->update(['two_factor_verified_at' => now()]);
        ActivityLog::record('2fa.verified', $user, "User {$user->email} verifikasi 2FA via API");

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('roles', 'permissions'),
            'token' => $token,
        ]);
    }

    private function issueOtpChallenge(User $user): JsonResponse
    {
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $challengeToken = Str::random(64);

        Cache::put('2fa_challenge:'.$challengeToken, [
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
        ], 600);

        if ($user->email) {
            try {
                Mail::raw("Kode OTP login Anda: {$otp}\n\nBerlaku 10 menit. Jangan share ke siapapun.", function ($m) use ($user) {
                    $m->to($user->email)->subject('Kode OTP Login — '.config('app.name'));
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => 'Akun ini memakai 2FA. Masukkan kode OTP yang dikirim ke email.',
            'challenge_token' => $challengeToken,
            'expires_in' => 600,
        ], 428);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out from all devices']);
    }
}
