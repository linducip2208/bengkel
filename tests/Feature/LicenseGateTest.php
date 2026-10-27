<?php

namespace Tests\Feature;

use App\Http\Middleware\PreventRequestForgery;
use App\Services\LicenseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * License gate (RequirePair) tidak pernah diuji karena semua test
 * mem-bypass middleware-nya. Test ini memakai stub LicenseClient
 * (tanpa HTTP, tanpa file lock) untuk dua cabang: belum pairing
 * dan sudah pairing.
 */
class LicenseGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            PreventRequestForgery::class,
        ]);
    }

    private function stubLicense(?array $data): void
    {
        $stub = new class($data) extends LicenseClient
        {
            public function __construct(private ?array $stubData) {}

            public function verify(string $domain): ?array
            {
                return $this->stubData;
            }
        };

        app()->instance(LicenseClient::class, $stub);
    }

    public function test_unpaired_blocks_protected_routes_to_wizard(): void
    {
        $this->stubLicense(null);

        $this->get('/')->assertRedirect('/__pair');
        $this->get('/dashboard')->assertRedirect('/__pair');
    }

    public function test_unpaired_still_allows_public_surfaces(): void
    {
        $this->stubLicense(null);

        $this->get('/login')->assertOk();
        $this->get('/booking')->assertOk();
        $this->get('/up')->assertOk();
    }

    public function test_paired_passes_license_gate_to_next_layer(): void
    {
        $this->stubLicense(['domain' => 'localhost', 'status' => 'active']);

        // Lolos RequirePair -> tertahan di auth (belum login) -> redirect login.
        $this->get(route('pos.terminal'))->assertRedirect(route('login'));

        // Halaman publik tetap 200.
        $this->get('/login')->assertOk();
    }
}
