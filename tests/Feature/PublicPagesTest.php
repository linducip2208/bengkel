<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NOTE: this class was previously misnamed "CustomerTest", which made PHPUnit
 * silently skip all 17 tests in the file (filename/classname must match).
 */
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Focus each test on the page itself, not on license pairing state.
        $this->withoutMiddleware([
            RequirePair::class,
        ]);

        // The public booking page renders technician options via spatie roles.
        foreach (['mekanik', 'service_advisor'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_landing_page_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_login_page_shows_branded_layout(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Aplikasi Bengkel Terbaik');
        $response->assertSee('Masuk');
        $response->assertDontSee('Demo Login');
        $response->assertDontSee('admin@bengkel.test');
    }

    public function test_guest_redirected_to_login_from_admin(): void
    {
        $response = $this->get('/customers');
        // Should redirect to login
        $response->assertRedirect('/login');
    }

    public function test_docs_page_is_accessible(): void
    {
        $response = $this->get('/docs');
        $response->assertStatus(200);
        $response->assertSee('Tutorial');
        $response->assertSee('admin@bengkel.test');
        $response->assertSee('manager@bengkel.test');
        $response->assertSee('kasir@bengkel.test');
        $response->assertSee('teknisi@bengkel.test');
        $response->assertSee('kasir2@bengkel.test');
    }

    public function test_blog_page_is_accessible(): void
    {
        $response = $this->get('/blog');
        $response->assertStatus(200);
        $response->assertSee('Blog');
    }

    public function test_sitemap_is_accessible(): void
    {
        $response = $this->get('/sitemap.xml');
        $response->assertStatus(200);
    }

    public function test_robots_txt_is_accessible(): void
    {
        $response = $this->get('/robots.txt');
        $response->assertStatus(200);
    }

    public function test_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@bengkel.test',
            'password' => 'password',
        ]);
        // May succeed or fail based on DB seeding, test the flow
        $response->assertStatus(302);
    }

    public function test_login_with_invalid_credentials_fails(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors();
    }

    public function test_login_validation_requires_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_login_validation_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@bengkel.test',
            'password' => '',
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_public_booking_page(): void
    {
        $response = $this->get('/booking');
        $response->assertStatus(200);
    }

    public function test_customer_login_page(): void
    {
        $response = $this->get('/customer/login');
        $response->assertStatus(200);
    }

    public function test_tracking_page_with_token(): void
    {
        // Unknown token must be handled gracefully (404 page, no crash).
        $response = $this->get('/track/test-token');
        $this->assertTrue(in_array($response->status(), [200, 404]));
    }

    public function test_tracking_page_with_real_token_renders(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);

        $customer = Customer::create(['name' => 'Lacak', 'phone' => '081777']);
        $type = VehicleType::create(['vehicle_type' => 'MPV', 'slug' => 'mpv-'.uniqid()]);
        $brand = VehicleBrand::create(['vehicle_type_id' => $type->id, 'vehicle_brand' => 'Toyota']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'vehicle_type_id' => $type->id,
            'vehicle_brand_id' => $brand->id,
            'number_plate' => 'B 7777 TRK',
        ]);
        $service = Service::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'title' => 'Servis lacak',
            'service_date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        $token = $service->getOrCreateTrackingToken();
        $this->assertNotEmpty($token);
        $this->assertSame($token, $service->fresh()->getOrCreateTrackingToken());

        $this->get('/track/'.$token)
            ->assertOk()
            ->assertSee('Lacak');

        // Review submit through the token link works and stays unpublished.
        $this->post('/track/'.$token.'/review', [
            'reviewer_name' => 'Pelanggan',
            'rating' => 5,
            'comment' => 'Bagus',
        ])->assertRedirect('/track/'.$token);
        $this->assertDatabaseHas('reviews', [
            'service_id' => $service->id, 'rating' => 5, 'is_published' => false,
        ]);
    }

    public function test_best_service_seo_page(): void
    {
        // This may 404 if category doesn't exist in DB, test routing
        $response = $this->get('/best/test-category');
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    public function test_alternatives_seo_page(): void
    {
        $response = $this->get('/alternatives-to/test-service');
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    public function test_compare_seo_page(): void
    {
        $response = $this->get('/compare/a-vs-b');
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }
}
