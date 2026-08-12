<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CashDenomination;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentMethod;
use App\Models\PaymentRecord;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductSellingPrice;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Models\Voucher;
use App\Http\Controllers\Tenant\LoyaltyController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (!$session) {
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
        $session->load('invoices');
        $expectedBalance = $session->opening_balance + $session->revenue;
        return view('pos.close', compact('session', 'expectedBalance'));
    }

    public function close(Request $request, PosSession $session): RedirectResponse
    {
        abort_unless($session->status === 'open', 404);

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
            'notes' => trim(($session->notes ? $session->notes . "\n---\n" : '') . ($validated['notes'] ?? '')),
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
            ->map(fn($p) => [
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
        if (!$customerId) {
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
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
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
        ]);

        $session = PosSession::findOrFail($validated['session_id']);
        if ($session->status !== 'open' || $session->user_id !== auth()->id()) {
            return back()->with('error', 'Sesi tidak valid.');
        }

        $subtotal = 0;
        foreach ($validated['items'] as $i) {
            $subtotal += $this->lineTotal($i);
        }
        $discount = (float) ($validated['discount'] ?? 0);
        $voucherDiscount = 0;
        $voucher = null;

        if (!empty($validated['voucher_id'])) {
            $voucher = Voucher::find($validated['voucher_id']);
            if ($voucher && $voucher->isUsable() && $subtotal >= $voucher->min_purchase) {
                $voucherDiscount = $voucher->calculateDiscount($subtotal - $discount);
            }
        }

        $grandTotal = max($subtotal - $discount - $voucherDiscount, 0);

        // Support split payment (multi metode) or single payment
        $payments = [];
        if (!empty($validated['payments'])) {
            $payments = $validated['payments'];
        } elseif (!empty($validated['payment_method_id'])) {
            $payments = [['method_id' => $validated['payment_method_id'], 'amount' => $validated['amount_paid']]];
        }

        $totalPaid = array_sum(array_column($payments, 'amount'));
        if ($totalPaid < $grandTotal) {
            return back()->withInput()->with('error', 'Total bayar kurang dari total belanja (kurang Rp ' . number_format($grandTotal - $totalPaid, 0, ',', '.') . ').');
        }

        $invoice = DB::transaction(function () use ($validated, $session, $subtotal, $discount, $grandTotal, $totalPaid, $payments, $voucher, $voucherDiscount) {
            $invoiceNumber = 'POS-' . now()->format('Ymd') . '-' . str_pad((string) ($session->transaction_count + 1), 4, '0', STR_PAD_LEFT);

            // Walk-in customer fallback (kolom invoices.customer_id NOT NULL)
            $customerId = $validated['customer_id'] ?? null;
            if (!$customerId) {
                $walkIn = \App\Models\Customer::withoutGlobalScopes()->firstOrCreate(
                    ['name' => 'Walk-in Customer', 'phone' => null],
                    ['address' => 'POS Counter']
                );
                $customerId = $walkIn->id;
            }

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customerId,
                'service_id' => null,
                'sale_id' => null,
                'pos_session_id' => $session->id,
                'payment_method_id' => $payments[0]['method_id'] ?? null,
                'payment_status' => 2,
                'total_amount' => $subtotal,
                'discount' => $discount,
                'tax_amount' => 0,
                'grand_total' => $grandTotal,
                'paid_amount' => $grandTotal,
                'amount_received' => $totalPaid,
                'invoice_date' => now()->toDateString(),
                'invoice_type' => 'pos',
                'created_by' => auth()->id(),
                'branch_id' => $session->branch_id,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::withoutGlobalScopes()->findOrFail($item['product_id']);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $this->lineTotal($item),
                    'discount' => $item['discount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                    'warranty_expiry' => $item['warranty_expiry'] ?? null,
                    'sold_date' => $item['sold_date'] ?? null,
                ]);

                $stock = StockRecord::withoutGlobalScopes()->where('product_id', $product->id)->first();
                if ($stock) {
                    $previous = $stock->quantity;
                    $stock->decrement('quantity', $item['quantity']);
                    StockHistory::create([
                        'product_id' => $product->id,
                        'quantity_change' => -$item['quantity'],
                        'previous_stock' => $previous,
                        'new_stock' => $previous - $item['quantity'],
                        'type' => 'pos',
                        'reference_type' => Invoice::class,
                        'reference_id' => $invoice->id,
                        'reason' => 'POS sale ' . $invoice->invoice_number,
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            $firstPayment = null;
            foreach ($payments as $pmt) {
                $record = PaymentRecord::create([
                    'invoice_id' => $invoice->id,
                    'payment_method_id' => $pmt['method_id'],
                    'amount' => $pmt['amount'],
                    'payment_date' => now(),
                    'reference_number' => $invoice->invoice_number,
                    'notes' => 'POS payment',
                ]);
                $firstPayment = $firstPayment ?? $record;
            }

            \App\Models\Income::create([
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $customerId,
                'payment_method_id' => $payments[0]['method_id'] ?? null,
                'amount' => $grandTotal,
                'income_date' => now(),
                'label' => 'POS ' . $invoice->invoice_number,
                'created_by' => auth()->id(),
            ]);

            if ($firstPayment) {
                try {
                    app(\App\Services\AutoJournalService::class)->journalInvoicePayment($firstPayment);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("POS auto-journal: {$e->getMessage()}");
                }
            }

            if ($voucher && $voucherDiscount > 0) {
                $voucher->increment('used_count');
                \App\Models\VoucherUsage::create([
                    'voucher_id' => $voucher->id,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customerId,
                    'discount_applied' => $voucherDiscount,
                ]);
            }

            return $invoice;
        });

        try {
            if ($invoice->customer_id) {
                LoyaltyController::earnFromInvoice($invoice);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Loyalty earn failed for POS: {$e->getMessage()}");
        }

        $changeAmount = max($totalPaid - $grandTotal, 0);
        $message = 'Transaksi POS sukses. Total: ' . number_format($grandTotal, 0, ',', '.');
        if ($voucher && $voucherDiscount > 0) {
            $message .= ' (hemat ' . number_format($voucherDiscount, 0, ',', '.') . ' dgn voucher ' . $voucher->code . ')';
        }
        $message .= ', Kembali: ' . number_format($changeAmount, 0, ',', '.');

        return redirect()->route('pos.receipt', $invoice)
            ->with('success', $message);
    }

    public function receipt(Invoice $invoice)
    {
        $invoice->load(['items.product', 'customer', 'paymentRecords.paymentMethod', 'posSession.user', 'voucherUsages.voucher']);
        $change = max($invoice->amount_received - $invoice->grand_total, 0);
        return view('pos.receipt', compact('invoice', 'change'));
    }
}
