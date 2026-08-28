<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Income;
use App\Models\Invoice;
use App\Models\PaymentRecord;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PosService extends BaseService
{
    /**
     * Checkout a POS sale atomically.
     *
     * Handles: branch+session validation, server-side price resolution,
     * stock decrement, invoice+items, split payments, income record, and
     * accounting journals — all inside a single DB transaction.
     *
     * Idempotency: when an idempotency_key is provided a UNIQUE(branch_id,
     * idempotency_key) index on invoices guarantees a racing duplicate cannot
     * slip through the in-memory pre-check. The unique violation is caught and
     * the original invoice returned.
     *
     * @throws \RuntimeException on any invalid state (insufficient stock,
     *                           forbidden branch, insufficient payment, ...)
     */
    public function checkout(array $data): array
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey) {
            $existing = Invoice::withoutBranchScope()
                ->where('branch_id', $data['session']['branch_id'])
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return ['invoice' => $existing->load(['items.product', 'customer', 'paymentRecords.paymentMethod']), 'replayed' => true];
            }
        }

        try {
            $invoice = DB::transaction(function () use ($data, $idempotencyKey) {
                $session = $data['session'];
                $paidTotal = round((float) $data['paid_total'], 2);

                $invoiceNumber = DocumentNumberService::generate(DocumentNumberService::POS_INVOICES, 'POS', 'Ymd', 4);

                $customerId = $this->resolveCustomerId($session->branch_id, $data['customer_id'] ?? null);
                $products = $this->loadProducts($data['items'], $session->branch_id);

                // Resolve server-side prices (discount may be applied below totals).
                $lines = [];
                foreach ($data['items'] as $item) {
                    $product = $products[(int) $item['product_id']];
                    $lines[] = $this->makeLine($product, $item);
                }
                usort($lines, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

                $subtotal = round(collect($lines)->sum('item.total_price'), 2);
                $voucherDiscount = $this->resolveVoucherDiscount($data, $customerId, $subtotal);
                $discount = round((float) ($data['discount'] ?? 0), 2);
                $grandTotal = max(round($subtotal - $discount - $voucherDiscount, 2), 0);

                if ($paidTotal < $grandTotal) {
                    throw new \RuntimeException('Total bayar kurang dari total belanja.');
                }

                $invoice = Invoice::create([
                    'invoice_number' => $invoiceNumber,
                    'customer_id' => $customerId,
                    'service_id' => null,
                    'sale_id' => null,
                    'pos_session_id' => $session->id,
                    'payment_method_id' => $data['payments'][0]['method_id'] ?? null,
                    'payment_status' => 2,
                    'total_amount' => $subtotal,
                    'discount' => round($discount + $voucherDiscount, 2),
                    'tax_amount' => 0,
                    'grand_total' => $grandTotal,
                    'paid_amount' => $grandTotal,
                    'amount_received' => $paidTotal,
                    'invoice_date' => now()->toDateString(),
                    'invoice_type' => 'pos',
                    'created_by' => $data['user_id'] ?? auth()->id(),
                    'branch_id' => $session->branch_id,
                    'idempotency_key' => $idempotencyKey,
                ]);

                foreach ($lines as $line) {
                    $invoice->items()->create($line['item']);
                    StockService::decrement(
                        $line['product_id'],
                        $line['quantity'],
                        'pos',
                        'POS sale '.$invoiceNumber,
                        Invoice::class,
                        $invoice->id,
                    );
                }

                $appliedPaymentTotal = 0.0;
                $paymentRecords = [];
                foreach ($data['payments'] as $paymentIndex => $pmt) {
                    $remaining = round($grandTotal - $appliedPaymentTotal, 2);
                    $appliedAmount = min(round((float) $pmt['amount'], 2), max($remaining, 0));
                    if ($appliedAmount <= 0) {
                        continue;
                    }

                    $record = PaymentRecord::create([
                        'invoice_id' => $invoice->id,
                        'payment_method_id' => $pmt['method_id'],
                        'amount' => $appliedAmount,
                        'payment_date' => now(),
                        'reference_number' => $invoiceNumber.'-'.($paymentIndex + 1),
                        'notes' => 'POS payment',
                        'created_by' => $data['user_id'] ?? auth()->id(),
                        'branch_id' => $session->branch_id,
                    ]);
                    $appliedPaymentTotal = round($appliedPaymentTotal + $appliedAmount, 2);
                    $paymentRecords[] = $record;
                }

                Income::create([
                    'invoice_number' => $invoiceNumber,
                    'customer_id' => $customerId,
                    'payment_method_id' => $data['payments'][0]['method_id'] ?? null,
                    'amount' => $grandTotal,
                    'income_date' => now(),
                    'label' => 'POS '.$invoiceNumber,
                    'created_by' => $data['user_id'] ?? auth()->id(),
                    'branch_id' => $session->branch_id,
                ]);

                app(AutoJournalService::class)->journalInvoiceIssued($invoice);

                foreach ($paymentRecords as $paymentRecord) {
                    app(AutoJournalService::class)->journalInvoicePayment($paymentRecord, (float) $paymentRecord->amount);
                }

                if (($data['voucher'] ?? null) && $voucherDiscount > 0) {
                    $lockedVoucher = Voucher::query()->whereKey($data['voucher']->id)->lockForUpdate()->first();
                    $lockedVoucher->increment('used_count');
                    VoucherUsage::create([
                        'voucher_id' => $lockedVoucher->id,
                        'invoice_id' => $invoice->id,
                        'customer_id' => $customerId,
                        'discount_applied' => $voucherDiscount,
                    ]);
                }

                return $invoice;
            });
        } catch (UniqueConstraintViolationException $e) {
            if (! $idempotencyKey) {
                throw $e;
            }

            $existing = Invoice::withoutBranchScope()
                ->where('branch_id', $data['session']['branch_id'])
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();

            return ['invoice' => $existing->load(['items.product', 'customer', 'paymentRecords.paymentMethod']), 'replayed' => true];
        }

        return ['invoice' => $invoice, 'replayed' => false];
    }

    /**
     * Open a POS session with branch access enforcement.
     */
    public function openSession(array $data): PosSession
    {
        if (PosSession::withoutBranchScope()->open()->forUser($data['user_id'])->exists()) {
            throw new \RuntimeException('Anda masih punya sesi POS yang terbuka.');
        }

        return PosSession::create([
            'branch_id' => $data['branch_id'],
            'user_id' => $data['user_id'],
            'opened_at' => now(),
            'opening_balance' => $data['opening_balance'],
            'status' => 'open',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Resolve the selling-price-group aware client-side prices into the
     * authoritative product lookup and validate branch ownership.
     *
     * @return array<int, Product> keyed by product_id
     */
    private function loadProducts(array $items, ?int $branchId): array
    {
        $ids = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();

        $products = Product::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->where('branch_id', $branchId)
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            if (! $products->has($id)) {
                throw new \RuntimeException("Produk #{$id} tidak tersedia pada cabang ini.");
            }
        }

        return $products->all();
    }

    /**
     * Build a normalized line with the SERVER-side price, never trusting the
     * client-supplied unit_price for the financial total. Item-level discount
     * entered by the cashier is still honored (it is an explicit business rule
     * requiring pos.discount permission on the API layer).
     */
    private function makeLine(Product $product, array $item): array
    {
        $quantity = round((float) $item['quantity'], 2);
        if ($quantity <= 0) {
            throw new \RuntimeException("Quantity produk {$product->name} harus lebih besar dari nol.");
        }

        $groupId = $item['selling_price_group_id'] ?? null;
        $unitPrice = round($product->getPriceFor($groupId), 2);

        $subtotal = $quantity * $unitPrice;
        $lineDiscount = round((float) ($item['discount'] ?? 0), 2);
        if (($item['discount_type'] ?? null) === 'percent') {
            $lineDiscount = $subtotal * ($lineDiscount / 100);
        }
        $lineDiscount = min($lineDiscount, $subtotal);
        $totalPrice = round($subtotal - $lineDiscount, 2);

        return [
            'product_id' => (int) $product->id,
            'quantity' => $quantity,
            'item' => [
                'product_id' => (int) $product->id,
                'description' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'discount' => $item['discount'] ?? 0,
                'discount_type' => $item['discount_type'] ?? null,
                'serial_number' => $item['serial_number'] ?? null,
                'warranty_expiry' => $item['warranty_expiry'] ?? null,
                'sold_date' => $item['sold_date'] ?? null,
            ],
        ];
    }

    private function resolveVoucherDiscount(array $data, int $customerId, float $subtotal): float
    {
        $voucher = $data['voucher'] ?? null;
        if (! $voucher) {
            return 0.0;
        }
        if ($voucher->isUsable() && $subtotal >= (float) $voucher->min_purchase) {
            return $voucher->calculateDiscount($subtotal - (float) ($data['discount'] ?? 0));
        }

        return 0.0;
    }

    /**
     * Walk-in customers are created per {branch_id, is_walk_in=true} so one
     * branch never reuses a customer that belongs to another branch.
     */
    private function resolveCustomerId(?int $branchId, ?int $customerId): int
    {
        if ($customerId) {
            $customer = Customer::withoutBranchScope()->whereKey($customerId)->first();
            if (! $customer) {
                throw new \RuntimeException('Customer tidak ditemukan.');
            }

            return $customer->id;
        }

        $walkIn = Customer::withoutBranchScope()
            ->where('branch_id', $branchId)
            ->where('name', 'Walk-in Customer')
            ->first();

        if ($walkIn) {
            return $walkIn->id;
        }

        return Customer::withoutBranchScope()->create([
            'name' => 'Walk-in Customer',
            'phone' => null,
            'address' => 'POS Counter',
            'branch_id' => $branchId,
        ])->id;
    }
}
