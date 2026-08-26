<?php

namespace Tests\Feature;

use App\Http\Middleware\RequirePair;
use App\Models\Customer;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentIdempotencyTest extends TestCase
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

    private function makeUser(string $role = 'kasir'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function makeUnpaidInvoice(float $grandTotal = 100000): Invoice
    {
        $customer = Customer::create(['name' => 'Cust Pay']);
        PaymentMethod::create(['payment' => 'Cash', 'slug' => 'cash-'.uniqid(), 'is_active' => true]);

        return Invoice::create([
            'invoice_number' => 'INV-PAY-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_status' => 0,
            'total_amount' => $grandTotal,
            'grand_total' => $grandTotal,
            'paid_amount' => 0,
            'amount_received' => 0,
            'invoice_date' => now()->toDateString(),
            'invoice_type' => 'service',
        ]);
    }

    private function postPayment(Invoice $invoice, float $amount): TestResponse
    {
        $method = PaymentMethod::first();

        return $this->post("/invoices/{$invoice->id}/payments", [
            'amount' => $amount,
            'payment_method_id' => $method->id,
            'payment_date' => now()->toDateTimeString(),
        ]);
    }

    public function test_overpayment_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        $invoice = $this->makeUnpaidInvoice(100000);
        $method = PaymentMethod::first();

        $response = $this->postPayment($invoice, 150000);

        $response->assertSessionHas('error');
        $this->assertEquals(0, PaymentRecord::count());
    }

    public function test_paying_a_settled_invoice_is_rejected(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        $invoice = $this->makeUnpaidInvoice(100000);
        $invoice->update(['paid_amount' => 100000, 'payment_status' => 2]);

        $response = $this->postPayment($invoice, 50000);

        $response->assertSessionHas('error');
        $this->assertEquals(0, PaymentRecord::count());
    }

    public function test_partial_payment_then_full_payment_marks_settled_once(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        $invoice = $this->makeUnpaidInvoice(100000);

        // Partial: 40k
        $this->postPayment($invoice, 40000)->assertRedirect();

        $invoice = Invoice::withoutGlobalScopes()->find($invoice->id);
        $this->assertEquals(1, $invoice->payment_status);
        $this->assertEquals(40000, (float) $invoice->paid_amount);

        // Settle: 60k
        $this->postPayment($invoice, 60000)->assertRedirect();

        $invoice = Invoice::withoutGlobalScopes()->find($invoice->id);
        $this->assertEquals(2, $invoice->payment_status);
        $this->assertEquals(100000, (float) $invoice->paid_amount);

        // Income recorded exactly once at settlement
        $this->assertEquals(
            1,
            Income::where('invoice_number', $invoice->invoice_number)->count()
        );
    }

    public function test_idempotency_key_returns_existing_payment_without_duplicating(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);
        $invoice = $this->makeUnpaidInvoice(100000);
        $method = PaymentMethod::first();
        $key = 'retry-key-12345';

        $first = app(PaymentService::class)->process(
            $invoice->fresh(),
            ['amount' => 100000, 'payment_method_id' => $method->id, 'idempotency_key' => $key]
        );

        $second = app(PaymentService::class)->process(
            $invoice->fresh(),
            ['amount' => 100000, 'payment_method_id' => $method->id, 'idempotency_key' => $key]
        );

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, PaymentRecord::where('invoice_id', $invoice->id)->count());
        $this->assertEquals(100000, (float) $invoice->fresh()->paid_amount);
    }
}
