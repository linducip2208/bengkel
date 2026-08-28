<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\PaymentLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Format-based adapter — bukan per-vendor.
 *
 * api_format = redirect      → Midtrans Snap, Doku, Xendit Invoice, Faspay redirect, dst
 * api_format = embed         → Midtrans Core API, Xendit checkout embed
 * api_format = qr            → QRIS aggregator (Mekari, OY!, dst)
 * api_format = manual_transfer → Bank transfer manual (no API call)
 *
 * Config gateway disimpan generic di DB: base_url, api_key, secret_key,
 * extra_headers (json), extra_config (json untuk body custom).
 */
class PaymentGatewayService
{
    /**
     * Generate payment link / QR untuk invoice yang sudah ada.
     */
    public function createForInvoice(Invoice $invoice, ?PaymentGateway $gateway = null): PaymentLink
    {
        $gateway ??= PaymentGateway::default();
        if (! $gateway) {
            throw new \RuntimeException('Belum ada Payment Gateway aktif. Setup dulu di /payment-gateways.');
        }

        $remaining = (float) $invoice->grand_total - (float) $invoice->paymentRecords()->sum('amount');
        if ($remaining <= 0) {
            throw new \RuntimeException('Invoice sudah lunas.');
        }

        $link = PaymentLink::create([
            'invoice_id' => $invoice->id,
            'payment_gateway_id' => $gateway->id,
            'token' => Str::random(40),
            'amount' => $remaining,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        try {
            match ($gateway->api_format) {
                'redirect' => $this->createRedirectPayment($link, $gateway, $invoice),
                'embed' => $this->createEmbedPayment($link, $gateway, $invoice),
                'qr' => $this->createQrPayment($link, $gateway, $invoice),
                'manual_transfer' => $this->createManualTransferPayment($link, $gateway, $invoice),
            };
        } catch (\Throwable $e) {
            Log::error('PG create failed: '.$e->getMessage());
            $link->update(['status' => 'failed', 'gateway_response' => ['error' => $e->getMessage()]]);
            throw $e;
        }

        return $link->fresh();
    }

    /**
     * Redirect-based: POST ke gateway, dapat URL redirect.
     * Body shape user-defined via extra_config; sistem inject standar field (order_id, amount).
     */
    private function createRedirectPayment(PaymentLink $link, PaymentGateway $gateway, Invoice $invoice): void
    {
        $endpoint = rtrim($gateway->base_url, '/').($gateway->callback_path ?: '/transactions');
        $body = array_merge([
            'order_id' => $link->token,
            'gross_amount' => (int) $link->amount,
            'customer_name' => $invoice->customer?->name ?? 'Walk-in',
            'customer_phone' => $invoice->customer?->phone ?? '',
            'customer_email' => $invoice->customer?->email ?? '',
            'item_description' => "Invoice {$invoice->invoice_number}",
            'callback_url' => route('payment.callback', $link->token),
        ], $gateway->extra_config ?? []);

        $headers = array_merge(['Accept' => 'application/json'], $gateway->extra_headers ?? []);
        if ($gateway->api_key) {
            $headers['Authorization'] = 'Basic '.base64_encode($gateway->api_key.':');
        }

        $response = Http::withHeaders($headers)->post($endpoint, $body);
        $json = $response->json();

        // Coba beberapa key umum yang dipakai gateway (universal extract)
        $redirectUrl = $json['redirect_url'] ?? $json['payment_url'] ?? $json['invoice_url'] ?? $json['url'] ?? null;
        $externalId = $json['transaction_id'] ?? $json['external_id'] ?? $json['id'] ?? null;

        $link->update([
            'payment_url' => $redirectUrl,
            'external_id' => $externalId,
            'gateway_response' => $json,
        ]);
    }

    private function createEmbedPayment(PaymentLink $link, PaymentGateway $gateway, Invoice $invoice): void
    {
        // Embed: dapat token/snap_token, frontend embed widget
        $endpoint = rtrim($gateway->base_url, '/').($gateway->callback_path ?: '/snap/v1/transactions');
        $body = array_merge([
            'order_id' => $link->token,
            'gross_amount' => (int) $link->amount,
        ], $gateway->extra_config ?? []);

        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        if ($gateway->api_key) {
            $headers['Authorization'] = 'Basic '.base64_encode($gateway->api_key.':');
        }
        $headers = array_merge($headers, $gateway->extra_headers ?? []);

        $response = Http::withHeaders($headers)->post($endpoint, $body);
        $json = $response->json();

        $link->update([
            'payment_url' => $json['snap_url'] ?? $json['payment_url'] ?? null,
            'external_id' => $json['token'] ?? $json['snap_token'] ?? null,
            'gateway_response' => $json,
        ]);
    }

    private function createQrPayment(PaymentLink $link, PaymentGateway $gateway, Invoice $invoice): void
    {
        $endpoint = rtrim($gateway->base_url, '/').($gateway->callback_path ?: '/qris/v1/dynamic-qr');
        $body = array_merge([
            'partner_reference_no' => $link->token,
            'amount' => ['value' => (string) $link->amount, 'currency' => 'IDR'],
        ], $gateway->extra_config ?? []);

        $headers = $gateway->extra_headers ?? [];
        if ($gateway->api_key) {
            $headers['X-API-KEY'] = $gateway->api_key;
        }

        $response = Http::withHeaders($headers)->post($endpoint, $body);
        $json = $response->json();

        $link->update([
            'qr_string' => $json['qr_content'] ?? $json['qr_string'] ?? $json['qrCode'] ?? null,
            'external_id' => $json['reference_id'] ?? $json['transaction_id'] ?? null,
            'gateway_response' => $json,
        ]);
    }

    private function createManualTransferPayment(PaymentLink $link, PaymentGateway $gateway, Invoice $invoice): void
    {
        // Manual transfer: tidak ada API call, cuma tampilkan instruksi
        $link->update([
            'gateway_response' => $gateway->extra_config ?: ['note' => 'Transfer manual sesuai instruksi gateway.'],
        ]);
    }

    /**
     * Webhook callback dari gateway. Universal extract status dari payload.
     */
    public function handleCallback(string $token, array $payload, array $headers = [], ?string $rawBody = null): PaymentLink
    {
        return DB::transaction(function () use ($token, $payload, $headers, $rawBody) {
            $link = PaymentLink::query()->where('token', $token)->lockForUpdate()->firstOrFail();
            $link->loadMissing(['gateway', 'invoice']);

            if ($link->expires_at !== null && Carbon::parse($link->expires_at)->isPast() && $link->status !== 'paid') {
                $link->update(['status' => 'expired']);
                throw new \RuntimeException('Payment link sudah kedaluwarsa.');
            }

            $this->verifyCallback($link, $payload, $headers, $rawBody);

            // Universal status extract — gateway umumnya pakai salah satu:
            $statusRaw = strtolower((string) (
                $payload['transaction_status'] ??
                $payload['status'] ??
                $payload['payment_status'] ??
                $payload['state'] ??
                ''
            ));

            $newStatus = match (true) {
                in_array($statusRaw, ['settlement', 'capture', 'paid', 'success', 'successful', 'completed']) => 'paid',
                in_array($statusRaw, ['expire', 'expired', 'timeout']) => 'expired',
                in_array($statusRaw, ['deny', 'failure', 'failed', 'rejected']) => 'failed',
                in_array($statusRaw, ['cancel', 'cancelled', 'void']) => 'cancelled',
                default => 'pending',
            };

            $link->update([
                'status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? now() : $link->paid_at,
                'gateway_response' => array_merge($link->gateway_response ?? [], ['callback' => $payload]),
            ]);

            // Kalau paid → reconcile via the canonical PaymentService so the
            // Kas/Income ledger, journal, activity log and loyalty stay
            // consistent with every other payment entry point.
            if ($newStatus === 'paid') {
                if (! $link->invoice) {
                    throw new \RuntimeException('Payment link tidak memiliki invoice.');
                }
                /** @var Invoice $invoice */
                $invoice = $link->invoice;

                $reportedAmount = $payload['gross_amount'] ?? $payload['amount'] ?? null;
                if (is_array($reportedAmount)) {
                    $reportedAmount = $reportedAmount['value'] ?? null;
                }
                if ($reportedAmount !== null && abs((float) $reportedAmount - (float) $link->amount) >= 0.01) {
                    throw new \RuntimeException('Nominal callback tidak sesuai dengan payment link.');
                }

                app(PaymentService::class)->process($invoice, [
                    'amount' => round((float) $link->amount, 2),
                    'payment_method_id' => $invoice->payment_method_id,
                    'payment_date' => now()->toDateTimeString(),
                    'reference_number' => $link->token,
                    'notes' => 'Auto-paid via '.($link->gateway?->name ?? 'PG'),
                    'idempotency_key' => 'gateway:'.$link->token,
                ]);
            }

            return $link->fresh();
        });
    }

    private function verifyCallback(PaymentLink $link, array $payload, array $headers, ?string $rawBody): void
    {
        $gateway = PaymentGateway::query()->find($link->payment_gateway_id);
        $configured = $gateway?->getAttribute('extra_config');
        $config = is_array($configured) ? $configured : [];
        $headerName = strtolower((string) ($config['webhook_signature_header'] ?? ''));

        if ($headerName === '') {
            if (($config['require_webhook_signature'] ?? false) === true) {
                throw new \RuntimeException('Header signature webhook belum dikonfigurasi.');
            }

            return;
        }

        $secret = $gateway?->getSecretKeyAttribute();
        if (! $secret) {
            throw new \RuntimeException('Secret webhook belum dikonfigurasi.');
        }

        $normalized = array_change_key_case($headers, CASE_LOWER);
        $providedHeader = $normalized[$headerName] ?? '';
        $provided = is_array($providedHeader)
            ? (string) ($providedHeader[0] ?? '')
            : (string) $providedHeader;
        $algorithm = (string) ($config['webhook_signature_algorithm'] ?? 'sha256');
        if (! in_array($algorithm, hash_hmac_algos(), true)) {
            throw new \RuntimeException('Algoritma signature webhook tidak didukung.');
        }

        $content = $rawBody ?? json_encode($payload, JSON_UNESCAPED_SLASHES);
        $expected = hash_hmac($algorithm, (string) $content, $secret);
        $provided = str_starts_with($provided, $algorithm.'=')
            ? substr($provided, strlen($algorithm) + 1)
            : $provided;

        if ($provided === '' || ! hash_equals($expected, $provided)) {
            throw new \RuntimeException('Signature webhook tidak valid.');
        }
    }
}
