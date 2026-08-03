<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderByDesc('is_default')->orderBy('name')->get();
        return view('payment-gateways.index', compact('gateways'));
    }

    public function create()
    {
        return view('payment-gateways.create', ['presets' => $this->loadPresets()]);
    }

    /**
     * Baca preset JSON files dari storage/app/payment-gateway-presets/.
     * HANYA untuk autofill convenience — code runtime tetap pakai data DB.
     * Owner bebas pilih preset, skip, atau edit setelah autofill.
     */
    public function presets(): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->loadPresets());
    }

    private function loadPresets(): array
    {
        $dir = storage_path('app/payment-gateway-presets');
        if (!is_dir($dir)) return [];
        $presets = [];
        foreach (glob($dir . '/*.json') as $file) {
            $slug = pathinfo($file, PATHINFO_FILENAME);
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) $presets[$slug] = $data + ['slug' => $slug];
        }
        ksort($presets);
        return $presets;
    }

    public function store(Request $request) { return $this->save($request); }

    public function edit(PaymentGateway $paymentGateway)
    {
        return view('payment-gateways.edit', ['gateway' => $paymentGateway, 'presets' => $this->loadPresets()]);
    }

    public function update(Request $request, PaymentGateway $paymentGateway) { return $this->save($request, $paymentGateway); }

    public function destroy(PaymentGateway $paymentGateway)
    {
        if ($paymentGateway->is_default) return back()->with('error', 'PG default tidak bisa dihapus, set yang lain dulu sebagai default.');
        ActivityLog::record('payment_gateway.delete', $paymentGateway, "Delete PG {$paymentGateway->name}");
        $paymentGateway->delete();
        return back()->with('success', 'Payment Gateway dihapus.');
    }

    public function generateLink(Invoice $invoice, PaymentGatewayService $service)
    {
        try {
            $link = $service->createForInvoice($invoice);
            return redirect()->route('invoices.show', $invoice)
                ->with('success', "Payment link dibuat. URL: {$link->payment_url}");
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /** Webhook callback dari gateway — public (no auth) */
    public function callback(Request $request, string $token, PaymentGatewayService $service)
    {
        try {
            $link = $service->handleCallback($token, $request->all());
            return response()->json(['status' => 'ok', 'payment_status' => $link->status]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    private function save(Request $request, ?PaymentGateway $gateway = null)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_format' => ['required', Rule::in(['redirect', 'embed', 'qr', 'manual_transfer'])],
            'base_url' => 'nullable|url',
            'merchant_id' => 'nullable|string|max:100',
            'api_key' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'callback_path' => 'nullable|string|max:255',
            'extra_headers' => 'nullable|string',
            'extra_config' => 'nullable|string',
            'supported_methods' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'sandbox_mode' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $extraHeaders = $validated['extra_headers'] ? json_decode($validated['extra_headers'], true) : null;
        $extraConfig = $validated['extra_config'] ? json_decode($validated['extra_config'], true) : null;
        $methods = $validated['supported_methods'] ? array_filter(array_map('trim', explode(',', $validated['supported_methods']))) : null;

        DB::transaction(function () use ($validated, $extraHeaders, $extraConfig, $methods, $request, &$gateway) {
            if ($request->boolean('is_default')) {
                PaymentGateway::where('is_default', true)->update(['is_default' => false]);
            }
            $data = [
                'name' => $validated['name'],
                'api_format' => $validated['api_format'],
                'base_url' => $validated['base_url'] ?? null,
                'merchant_id' => $validated['merchant_id'] ?? null,
                'callback_path' => $validated['callback_path'] ?? null,
                'extra_headers' => $extraHeaders,
                'extra_config' => $extraConfig,
                'supported_methods' => $methods,
                'is_active' => $request->boolean('is_active', true),
                'is_default' => $request->boolean('is_default'),
                'sandbox_mode' => $request->boolean('sandbox_mode'),
                'description' => $validated['description'] ?? null,
            ];

            if ($gateway) {
                $gateway->update($data);
                if (!empty($validated['api_key'])) $gateway->api_key = $validated['api_key'];
                if (!empty($validated['secret_key'])) $gateway->secret_key = $validated['secret_key'];
                $gateway->save();
            } else {
                $gateway = PaymentGateway::create($data);
                if (!empty($validated['api_key'])) { $gateway->api_key = $validated['api_key']; $gateway->save(); }
                if (!empty($validated['secret_key'])) { $gateway->secret_key = $validated['secret_key']; $gateway->save(); }
            }
        });

        ActivityLog::record('payment_gateway.save', $gateway, "Saved PG {$gateway->name}");
        return redirect()->route('payment-gateways.index')->with('success', 'Payment Gateway tersimpan.');
    }
}
