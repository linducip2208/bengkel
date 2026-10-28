<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Portal customer publik (permukaan tanpa test): login password default,
 * throttle brute-force, isolasi data antar customer, guard sesi.
 */
class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            RequirePair::class,
            PreventRequestForgery::class,
        ]);
    }

    public function test_login_with_default_password_finds_normalized_phone(): void
    {
        // Nomor tersimpan ternormalisasi (+62…) oleh Customer::saving.
        $customer = Customer::create(['name' => 'Portal Uji', 'phone' => '0817000001']);
        $this->assertSame('+62817000001', $customer->fresh()->phone);

        // Password default = 6 digit terakhir HP.
        $response = $this->post(route('customer.login.submit'), [
            'phone' => '0817000001',
            'password' => '000001',
        ]);

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertSame($customer->id, session('customer_id'));
    }

    public function test_login_wrong_password_rejected(): void
    {
        Customer::create(['name' => 'Portal Salah', 'phone' => '0817000002']);

        $this->post(route('customer.login.submit'), [
            'phone' => '0817000002',
            'password' => 'salah-total',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertNull(session('customer_id'));
    }

    public function test_login_with_set_password(): void
    {
        $customer = Customer::create(['name' => 'Portal Set', 'phone' => '0817000003']);
        $customer->update(['portal_password' => Hash::make('rahasia-aman')]);

        $this->post(route('customer.login.submit'), [
            'phone' => '0817000003',
            'password' => 'rahasia-aman',
        ])->assertRedirect(route('customer.dashboard'));
    }

    public function test_login_is_throttled(): void
    {
        Customer::create(['name' => 'Portal Brute', 'phone' => '0817000004']);

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('customer.login.submit'), [
                'phone' => '0817000004',
                'password' => 'salah',
            ]);
        }

        // Upaya ke-11 dalam semenit: 429.
        $this->post(route('customer.login.submit'), [
            'phone' => '0817000004',
            'password' => 'salah',
        ])->assertStatus(429);
    }

    public function test_customer_cannot_view_other_customer_invoice(): void
    {
        $a = Customer::create(['name' => 'Cust A', 'phone' => '0817000005']);
        $b = Customer::create(['name' => 'Cust B', 'phone' => '0817000006']);
        $invoiceB = Invoice::create([
            'invoice_number' => 'INV-POR-'.uniqid(),
            'customer_id' => $b->id,
            'payment_status' => 0,
            'total_amount' => 100000,
            'grand_total' => 100000,
            'paid_amount' => 0,
            'amount_received' => 0,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'service',
        ]);

        $this->post(route('customer.login.submit'), [
            'phone' => '0817000005',
            'password' => substr('0817000005', -6),
        ])->assertRedirect(route('customer.dashboard'));
        $this->assertSame($a->id, session('customer_id'));

        $this->get(route('customer.invoice', $invoiceB))->assertNotFound();
    }

    public function test_dashboard_requires_login(): void
    {
        $this->get(route('customer.dashboard'))->assertForbidden();
    }
}
