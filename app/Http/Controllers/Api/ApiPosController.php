<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiPosController extends Controller
{
    public function __construct(private PosService $posService) {}

    public function openSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $branchId = (int) $validated['branch_id'];
        $user = auth()->user();

        if (! $user->hasBranchAccess($branchId)) {
            return response()->json(['message' => 'Cabang tidak dapat diakses.'], 403);
        }

        try {
            $session = $this->posService->openSession([
                'branch_id' => $branchId,
                'user_id' => auth()->id(),
                'opening_balance' => $validated['opening_balance'],
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($session, 201);
    }

    public function closeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $session = PosSession::withoutBranchScope()->open()->forUser(auth()->id())->first();
        if (! $session) {
            return response()->json(['message' => 'Tidak ada sesi POS yang terbuka.'], 404);
        }

        $expected = $session->opening_balance + $session->revenue;

        $session->update([
            'closed_at' => now(),
            'closing_balance' => $validated['closing_balance'],
            'expected_balance' => $expected,
            'difference' => $validated['closing_balance'] - $expected,
            'status' => 'closed',
            'notes' => trim(($session->notes ? $session->notes."\n---\n" : '').($validated['notes'] ?? '')),
        ]);

        return response()->json($session);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:pos_sessions,id',
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:fixed,percent',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method_id' => 'required|integer',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        $user = auth()->user();
        $session = PosSession::withoutBranchScope()->find($validated['session_id']);
        if (! $session || $session->status !== 'open' || $session->user_id !== auth()->id()) {
            return response()->json(['message' => 'Sesi tidak valid.'], 422);
        }
        if (! $user->hasBranchAccess($session->branch_id)) {
            return response()->json(['message' => 'Cabang sesi tidak dapat diakses.'], 403);
        }

        // Payment method must belong to the same tenant context.
        $paymentMethod = PaymentMethod::withoutGlobalScopes()->find($validated['payment_method_id']);
        if (! $paymentMethod) {
            return response()->json(['message' => 'Metode pembayaran tidak valid.'], 422);
        }

        // Human-initiated discount & price overrides require dedicated roles.
        $hasDiscountRole = $user->hasAnyRole(['super_admin', 'admin', 'manager']);
        if (! $hasDiscountRole && ((float) ($validated['discount'] ?? 0) > 0 || collect($validated['items'])->contains(fn ($i) => (float) ($i['discount'] ?? 0) > 0))) {
            return response()->json(['message' => 'Anda tidak berhak memberikan diskon POS.'], 403);
        }

        // Validate customer belongs to the session's branch (cross-branch denied).
        if (! empty($validated['customer_id'])) {
            $customer = Customer::withoutBranchScope()->whereKey($validated['customer_id'])->first();
            if (! $customer || ($session->branch_id !== null && $customer->branch_id !== $session->branch_id)) {
                return response()->json(['message' => 'Customer tidak tersedia pada cabang sesi ini.'], 422);
            }
        }

        try {
            // Resolve authoritative products & server-side prices inside service.
            $productIds = collect($validated['items'])->pluck('product_id')->map(fn ($id) => (int) $id)->all();
            $products = Product::withoutGlobalScopes()
                ->whereIn('id', $productIds)
                ->where('branch_id', $session->branch_id)
                ->get()
                ->keyBy('id');
            foreach ($productIds as $pid) {
                if (! $products->has($pid)) {
                    return response()->json(['message' => "Produk #{$pid} tidak tersedia pada cabang ini."], 422);
                }
            }

            $customer = null;
            if (! empty($validated['customer_id'])) {
                $customer = Customer::withoutBranchScope()->find($validated['customer_id']);
            }
            /** @var CustomerGroup|null $customerGroup */
            $customerGroup = $customer?->customerGroup;
            $sellingPriceGroupId = $customerGroup?->selling_price_group_id;

            $items = $validated['items'];
            foreach ($items as &$item) {
                $item['selling_price_group_id'] = $sellingPriceGroupId;
            }
            unset($item);

            $result = $this->posService->checkout([
                'session' => $session,
                'user_id' => auth()->id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'items' => $items,
                'discount' => $validated['discount'] ?? 0,
                'paid_total' => (float) $validated['amount_paid'],
                'payments' => [[
                    'method_id' => $validated['payment_method_id'],
                    'amount' => (float) $validated['amount_paid'],
                ]],
                'idempotency_key' => $validated['idempotency_key'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $invoice = $result['invoice'];
        $grandTotal = (float) $invoice->grand_total;
        $changeAmount = max((float) $validated['amount_paid'] - $grandTotal, 0);

        return response()->json([
            'message' => 'Transaksi POS sukses.',
            'change' => $changeAmount,
            'invoice' => $invoice->load(['items.product', 'customer', 'paymentRecords.paymentMethod']),
        ], 201);
    }
}
