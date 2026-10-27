<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Login API tidak boleh menerbitkan token Sanctum bila akun memakai 2FA
 * — harus lewat challenge OTP dulu (sebelumnya: bypass langsung).
 * Alur sukses penuh (baca OTP dari email) diverifikasi manual.
 */
class ApiTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $twoFactor = false): User
    {
        return User::factory()->create([
            'is_active' => true,
            'two_factor_enabled' => $twoFactor,
            'password' => 'secret123',
        ]);
    }

    public function test_non_2fa_login_still_issues_token(): void
    {
        $user = $this->makeUser(false);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
    }

    public function test_2fa_login_returns_challenge_without_token(): void
    {
        $user = $this->makeUser(true);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $response->assertStatus(428)->assertJsonStructure(['message', 'challenge_token', 'expires_in']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_2fa_verify_wrong_code_rejected(): void
    {
        $user = $this->makeUser(true);

        $challenge = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->json('challenge_token');

        $this->postJson('/api/v1/2fa/verify', [
            'challenge_token' => $challenge,
            'code' => '000000',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_2fa_verify_unknown_challenge_rejected(): void
    {
        $this->postJson('/api/v1/2fa/verify', [
            'challenge_token' => 'tidak-ada-token-ini',
            'code' => '123456',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_2fa_verify_validation(): void
    {
        $this->postJson('/api/v1/2fa/verify', [])->assertStatus(422);
    }

    public function test_2fa_verify_correct_code_issues_token(): void
    {
        $user = $this->makeUser(true);

        $challenge = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->json('challenge_token');

        // Skenario sama seperti membaca OTP dari email: challenge
        // di-cache ulang dengan kode yang diketahui untuk uji logika verify.
        Cache::put('2fa_challenge:'.$challenge, [
            'user_id' => $user->id,
            'otp_hash' => Hash::make('123456'),
        ], 600);

        $response = $this->postJson('/api/v1/2fa/verify', [
            'challenge_token' => $challenge,
            'code' => '123456',
        ]);

        $response->assertOk()->assertJsonStructure(['user', 'token']);
        $this->assertNotNull($user->fresh()->two_factor_verified_at);
    }
}
