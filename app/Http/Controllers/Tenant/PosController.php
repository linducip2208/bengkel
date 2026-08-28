<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashDenomination;
use App\Models\Customer;
use App\Models\HeldPosTransaction;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductSellingPrice;
use App\Models\Voucher;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PosController extends Controller
{
    public function sessions(Request $request)
    {
        $query = PosSession::with(['user', 'branch'])->latest('opened_at');
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $sessions = $query->paginate(20)->withQueryString();

        return view('pos.sessions', compact('sessions'));
    }

    /**
     * Main POS terminal — cart interface for selling parts.
     */
    public function terminal()
    {
        $userId = auth()->id();
        $session = PosSession::open()->forUser($userId)->latest('opened_at')->first();

        if (! $session) {
            return redirect()->route('pos.openForm');
        }

        $customers = Customer::with('customerGroup')->orderBy('name')->limit(500)->get();
        $paymentMethods = PaymentMethod::where('is_active', true)->orderBy('payment')->get();
        $products = Product::with('stockRecord')->orderBy('name')->limit(1000)->get();

        return view('pos.terminal', compact('session', 'customers', 'paymentMethods', 'products'));
    }

    public function openForm()
    {
        // Cek apakah user sudah ada session terbuka
        $existing = PosSession::open()->forUser(auth()->id())->first();
        if ($existing) {
            return redirect()->route('pos.terminal');
        }

        return view('pos.open');
    }

    public function open(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $existing = PosSession::open()->forUser(auth()->id())->exists();
        if ($existing) {
            return back()->with('error', 'Anda masih punya sesi POS yang terbuka.');
        }

        PosSession::create([
            'branch_id' => session('current_branch_id'),
            'user_id' => auth()->id(),
            'opened_at' => now(),
            'opening_balance' => $validated['opening_balance'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('pos.terminal')->with('success', 'Sesi kasir dibuka.');
    }

    public function closeForm(PosSession $session)
    {
        abort_unless($session->status === 'open', 404);
        $this->authorizeSessionOwner($session);
        $session->load('invoices');
        $expectedBalance = $session->opening_balance + $session->revenue;

        return view('pos.close', compact('session', 'expectedBalance'));
    }

    public function close(Request $request, PosSession $session): RedirectResponse
    {
        abort_unless($session->status === 'open', 404);
        $this->authorizeSessionOwner($session);

        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'denominations' => 'nullable|array',
            'denominations.*.denomination' => 'required_with:denominations|integer|min:1',
            'denominations.*.count' => 'required_with:denominations|integer|min:0',
        ]);

        $expected = $session->opening_balance + $session->revenue;

        $physicalTotal = null;
        $hasPhysicalCount = false;
        if (! empty($validated['denominations'])) {
            DB::transaction(function () use ($session, $validated, &$physicalTotal, &$hasPhysicalCount) {
                $total = 0;
                foreach ($validated['denominations'] as $item) {
                    $denomination = (int) $item['denomination'];
                    $count = (int) $item['count'];
                    if ($count <= 0) {
                        continue;
                    }
                    $hasPhysicalCount = true;
                    $subtotal = $denomination * $count;
                    $total += $subtotal;

                    CashDenomination::create([
                        'pos_session_id' => $session->id,
                        'denomination' => $denomination,
                        'count' => $count,
                        'subtotal' => $subtotal,
                    ]);
                }
                $physicalTotal = $total;
            });
        }

        $closingBalance = $hasPhysicalCount ? $physicalTotal : $validated['closing_balance'];

        $session->update([
            'closed_at' => now(),
            'closing_balance' => $closingBalance,
            'expected_balance' => $expected,
            'difference' => $closingBalance - $expected,
            'status' => 'closed',
            'notes' => trim(($session->notes ? $session->notes."\n---\n" : '').($validated['notes'] ?? '')),
        ]);

        return redirect()->route('pos.sessions')->with('success', 'Sesi kasir ditutup.');
    }

    /**
     * AJAX endpoint untuk cari produk by barcode/kode/nama.
     */
    public function searchProduct(Request $request): JsonResponse
    {
        $q = trim((string) $request->q);
        if ($q === '') {
            return response()->json([]);
        }
        $groupId = $this->resolveSellingPriceGroupId($request->input('customer_id'));
        $products = Product::with('stockRecord')
            ->where(function ($qq) use ($q) {
                $qq->where('code', $q)
                    ->orWhere('barcode', $q)
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('product_no', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get(['id', 'product_no', 'code', 'barcode', 'name', 'price'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'price' => $p->getPriceFor($groupId),
                'stock' => $p->stockRecord?->quantity ?? 0,
            ]);

        return response()->json($products);
    }

    /**
     * AJAX endpoint — harga jual per produk untuk customer (group-aware).
     */
    public function prices(Request $request): JsonResponse
    {
        $groupId = $this->resolveSellingPriceGroupId($request->input('customer_id'));

        $overrides = collect();
        if ($groupId) {
            $overrides = ProductSellingPrice::where('selling_price_group_id', $groupId)
                ->pluck('price', 'product_id');
        }

        $prices = Product::query()->pluck('price', 'id')
            ->map(fn ($price, $id) => (float) ($overrides->get($id, $price)));

        return response()->json($prices);
    }

    protected function resolveSellingPriceGroupId($customerId): ?int
    {
        if (! $customerId) {
            return null;
        }

        $customer = Customer::with('customerGroup')->find($customerId);

        return $customer?->customerGroup?->selling_price_group_id;
    }

    /**
     * Line total after line-level discount: (unit_price * quantity) - discount.
     */
    protected function lineTotal(array $item): float
    {
        $subtotal = (float) $item['quantity'] * (float) $item['unit_price'];
        $discount = (float) ($item['discount'] ?? 0);

        if (($item['discount_type'] ?? null) === 'percent') {
            $discount = $subtotal * ($discount / 100);
        }

        return round($subtotal - min($discount, $subtotal), 2);
    }

    /**
     * Submit cart → bikin Invoice + PaymentRecord + kurangi stok.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:pos_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:fixed,percent',
            'items.*.serial_number' => 'nullable|string|max:255',
            'items.*.warranty_expiry' => 'nullable|date',
            'items.*.sold_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'payments' => 'nullable|array|min:1',
            'payments.*.method_id' => 'required_with:payments|exists:payment_methods,id',
            'payments.*.amount' => 'required_with:payments|numeric|min:1',
            'voucher_id' => 'nullable|exists:vouchers,id',
            'voucher_discount' => 'nullable|numeric|min:0',
            'idempotency_key' => 'nullable|string|max:64',
        ]);

        // Double-submit guard: same key already checked out → show that receipt.
        if (! empty($validated['idempotency_key'])) {
            $existing = Invoice::where('idempotency_key', $validated['idempotency_key'])->first();
            if ($existing) {
                return redirect()->route('pos.receipt', $existing)
                    ->with('info', 'Transaksi ini sudah pernah diproses.');
            }
        }

        $session = PosSession::findOrFail($validated['session_id']);
        if ($session->status !== 'open' || $session->user_id !== auth()->id()) {
            return back()->with('error', 'Sesi tidak valid.');
        }

        // Support split payment (multi metode) or single payment
        $payments = [];
        if (! empty($validated['payments'])) {
            $payments = $validated['payments'];
        } elseif (! empty($validated['payment_method_id'])) {
            $payments = [['method_id' => $validated['payment_method_id'], 'amount' => $validated['amount_paid']]];
        }

        $totalPaid = round(array_sum(array_column($payments, 'amount')), 2);

        $voucher = null;
        if (! empty($validated['voucher_id'])) {
            $voucher = Voucher::find($validated['voucher_id']);
        }

        try {
            $result = app(PosService::class)->checkout([
                'session' => $session,
                'user_id' => auth()->id(),
                'customer_id' => $validated['customer_id'] ?? null,
                'items' => $validated['items'],
                'discount' => $validated['discount'] ?? 0,
                'paid_total' => $totalPaid,
                'payments' => $payments,
                'voucher' => $voucher,
                'idempotency_key' => $validated['idempotency_key'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $invoice = $result['invoice'];

        try {
            if ($invoice->customer_id) {
                LoyaltyController::earnFromInvoice($invoice);
            }
        } catch (\Throwable $e) {
            Log::warning("Loyalty earn failed for POS: {$e->getMessage()}");
        }

        $grandTotal = (float) $invoice->grand_total;
        $discount = (float) $request->input('discount', 0);
        if ($discount > 0) {
            ActivityLog::record('pos.discount', null, 'Diskon POS Rp '.number_format($discount, 0, ',', '.').' (subtotal Rp '.number_format($grandTotal, 0, ',', '.').')');
        }

        $voucherDiscount = (float) ($validated['voucher_discount'] ?? 0);
        $changeAmount = max($totalPaid - $grandTotal, 0);
        $message = 'Transaksi POS sukses. Total: '.number_format($grandTotal, 0, ',', '.');
        if ($voucher && $voucherDiscount > 0) {
            $message .= ' (hemat '.number_format($voucherDiscount, 0, ',', '.').' dgn voucher '.$voucher->code.')';
        }
        $message .= ', Kembali: '.number_format($changeAmount, 0, ',', '.');

        return redirect()->route('pos.receipt', $invoice)
            ->with('success', $message);
    }

    public function receipt(Invoice $invoice)
    {
        $invoice->load(['items.product', 'customer', 'paymentRecords.paymentMethod', 'posSession.user', 'voucherUsages.voucher']);
        $change = max($invoice->amount_received - $invoice->grand_total, 0);

        return view('pos.receipt', compact('invoice', 'change'));
    }

    /**
     * Tahan (suspend) transaksi POS — simpan item keranjang sebagai JSON.
     */
    public function hold(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'nullable|exists:pos_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:fixed,percent',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $held = HeldPosTransaction::create([
            'session_id' => $validated['session_id'] ?? null,
            'user_id' => auth()->id(),
            'customer_id' => $validated['customer_id'] ?? null,
            'items' => array_values($validated['items']),
            'discount' => $validated['discount'] ?? 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Transaksi ditahan.',
            'held_id' => $held->id,
        ]);
    }

    /**
     * Recall transaksi yang ditahan — kembalikan item sebagai JSON.
     */
    public function recall(HeldPosTransaction $held): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'held' => [
                'id' => $held->id,
                'items' => $held->items,
                'discount' => (float) $held->discount,
                'customer_id' => $held->customer_id,
                'notes' => $held->notes,
                'created_at' => $held->created_at?->format('d M H:i'),
            ],
        ]);
    }

    /**
     * Hapus transaksi yang ditahan.
     */
    public function releaseHeld(HeldPosTransaction $held): JsonResponse
    {
        abort_unless($held->user_id === auth()->id(), 403, 'Bukan transaksi tahan Anda.');

        $held->delete();

        return response()->json(['ok' => true, 'message' => 'Transaksi ditahan dihapus.']);
    }

    /**
     * POS session close/recall hanya boleh oleh pemilik sesi atau atasan.
     */
    protected function authorizeSessionOwner(PosSession $session): void
    {
        $isOwner = $session->user_id === auth()->id();
        $isSupervisor = auth()->user()?->hasRole(['super_admin', 'admin', 'manager']);

        abort_unless($isOwner || $isSupervisor, 403, 'Anda tidak berhak menutup sesi kasir ini.');
    }

    /**
     * Daftar transaksi yang ditahan (untuk modal Recall).
     */
    public function heldList(): JsonResponse
    {
        $helds = HeldPosTransaction::with('customer')
            ->where('user_id', auth()->id())
            ->latest()
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'customer' => $h->customer?->name ?? 'Walk-in',
                'items_count' => count($h->items ?? []),
                'discount' => (float) $h->discount,
                'notes' => $h->notes,
                'created_at' => $h->created_at?->format('d M H:i'),
            ]);

        return response()->json(['ok' => true, 'helds' => $helds]);
    }
}
