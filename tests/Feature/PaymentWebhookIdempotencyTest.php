<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\PaymentLink;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_signed_callback_creates_one_payment_only(): void
    {
        $fixture = $this->makePaymentLink();
        $payload = ['status' => 'paid', 'amount' => '100000.00'];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $body, 'webhook-secret');

        $service = app(PaymentGatewayService::class);
        $service->handleCallback($fixture->token, $payload, ['x-signature' => [$signature]], $body);
        $service->handleCallback($fixture->token, $payload, ['x-signature' => [$signature]], $body);

        $this->assertSame(1, PaymentRecord::where('invoice_id', $fixture->invoice_id)->count());
        $this->assertSame(100000.0, (float) $fixture->invoice->fresh()->paid_amount);
    }

    public function test_invalid_signature_rolls_back_callback(): void
    {
        $fixture = $this->makePaymentLink();
        $payload = ['status' => 'paid', 'amount' => '100000.00'];

        try {
            app(PaymentGatewayService::class)->handleCallback(
                $fixture->token,
                $payload,
                ['x-signature' => ['invalid']],
                json_encode($payload, JSON_UNESCAPED_SLASHES),
            );
            $this->fail('Invalid signature should have been rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Signature webhook tidak valid.', $exception->getMessage());
        }

        $this->assertSame('pending', $fixture->fresh()->status);
        $this->assertSame(0, PaymentRecord::where('invoice_id', $fixture->invoice_id)->count());
    }

    public function test_amount_mismatch_rolls_back_callback(): void
    {
        $fixture = $this->makePaymentLink();
        $payload = ['status' => 'paid', 'amount' => '90000.00'];
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $body, 'webhook-secret');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Nominal callback tidak sesuai');

        try {
            app(PaymentGatewayService::class)->handleCallback(
                $fixture->token,
                $payload,
                ['x-signature' => [$signature]],
                $body,
            );
        } finally {
            $this->assertSame('pending', $fixture->fresh()->status);
            $this->assertSame(0, PaymentRecord::where('invoice_id', $fixture->invoice_id)->count());
        }
    }

    private function makePaymentLink(): PaymentLink
    {
        $this->actingAs(User::factory()->create(['is_active' => true]));
        $customer = Customer::create(['name' => 'Webhook Customer']);
        $method = PaymentMethod::create([
            'payment' => 'Gateway',
            'slug' => 'gateway-'.uniqid(),
            'is_active' => true,
        ]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-WEBHOOK-'.uniqid(),
            'customer_id' => $customer->id,
            'payment_method_id' => $method->id,
            'payment_status' => 0,
            'total_amount' => 100000,
            'grand_total' => 100000,
            'paid_amount' => 0,
            'amount_received' => 0,
            'invoice_date' => now(),
            'invoice_type' => 'service',
        ]);
        $gateway = PaymentGateway::create([
            'name' => 'Configured Gateway',
            'api_format' => 'redirect',
            'extra_config' => [
                'require_webhook_signature' => true,
                'webhook_signature_header' => 'x-signature',
                'webhook_signature_algorithm' => 'sha256',
            ],
            'is_active' => true,
        ]);
        $gateway->secret_key = 'webhook-secret';
        $gateway->save();

        return PaymentLink::create([
            'invoice_id' => $invoice->id,
            'payment_gateway_id' => $gateway->id,
            'token' => str_repeat('a', 40),
            'amount' => 100000,
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);
    }
}
