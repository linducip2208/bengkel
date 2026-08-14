<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentRecord;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\StockHistory;
use App\Models\StockRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiPosController extends Controller
{
    public function openSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
            'notes' => 'nullable|string',
        ]);

        $existing = PosSession::open()->forUser(auth()->id())->exists();
        if ($existing) {
            return response()->json(['message' => 'Anda masih punya sesi POS yang terbuka.'], 422);
        }

        $session = PosSession::create([
            'branch_id' => $validated['branch_id'] ?? null,
            'user_id' => auth()->id(),
            'opened_at' => now(),
            'opening_balance' => $validated['opening_balance'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json($session, 201);
    }

    public function closeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $session = PosSession::open()->forUser(auth()->id())->first();
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
            'notes' => trim(($session->notes ? $session->notes . "\n---\n" : '') . ($validated['notes'] ?? '')),
        ]);

        return response()->json($session);
    }

    protected function lineTotal(array $item): float
    {
        $subtotal = (float) $item['quantity'] * (float) $item['unit_price'];
        $discount = (float) ($item['discount'] ?? 0);

        if (($item['discount_type'] ?? null) === 'percent') {
            $discount = $subtotal * ($discount / 100);
        }

        return round($subtotal - min($discount, $subtotal), 2);
    }

    public function checkout(Request $request): JsonResponse
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
            'discount' => 'nullable|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ]);

        $session = PosSession::findOrFail($validated['session_id']);
        if ($session->status !== 'open' || $session->user_id !== auth()->id()) {
            return response()->json(['message' => 'Sesi tidak valid.'], 422);
        }

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $subtotal += $this->lineTotal($item);
        }
        $discount = (float) ($validated['discount'] ?? 0);
        $grandTotal = max($subtotal - $discount, 0);

        if ((float) $validated['amount_paid'] < $grandTotal) {
            return response()->json([
                'message' => 'Total bayar kurang dari total belanja.',
                'shortfall' => $grandTotal - (float) $validated['amount_paid'],
            ], 422);
        }

        $invoice = DB::transaction(function () use ($validated, $session, $subtotal, $discount, $grandTotal) {
            $invoiceNumber = 'POS-' . now()->format('Ymd') . '-' . str_pad((string) ($session->transaction_count + 1), 4, '0', STR_PAD_LEFT);

            $customerId = $validated['customer_id'] ?? null;
            if (! $customerId) {
                $walkIn = Customer::withoutGlobalScopes()->firstOrCreate(
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
                'payment_method_id' => $validated['payment_method_id'],
                'payment_status' => 2,
                'total_amount' => $subtotal,
                'discount' => $discount,
                'tax_amount' => 0,
                'grand_total' => $grandTotal,
                'paid_amount' => $grandTotal,
                'amount_received' => (float) $validated['amount_paid'],
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

            PaymentRecord::create([
                'invoice_id' => $invoice->id,
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $grandTotal,
                'payment_date' => now(),
                'reference_number' => $invoice->invoice_number,
                'notes' => 'POS payment',
            ]);

            Income::create([
                'invoice_number' => $invoice->invoice_number,
                'customer_id' => $customerId,
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $grandTotal,
                'income_date' => now(),
                'label' => 'POS ' . $invoice->invoice_number,
                'created_by' => auth()->id(),
                'branch_id' => $session->branch_id,
            ]);

            return $invoice;
        });

        $changeAmount = max((float) $validated['amount_paid'] - $grandTotal, 0);

        return response()->json([
            'message' => 'Transaksi POS sukses.',
            'change' => $changeAmount,
            'invoice' => $invoice->load(['items.product', 'customer', 'paymentRecords.paymentMethod']),
        ], 201);
    }
}
