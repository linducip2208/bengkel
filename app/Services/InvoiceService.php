<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\InvoiceScheme;
use App\Models\Product;
use App\Models\Service;
use App\Models\StockRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService extends BaseService
{
    public function create(array $data): Invoice
    {
        if (($data['invoice_type'] ?? null) === 'service' && ! empty($data['service_id'])) {
            /** @var Service $service */
            $service = Service::query()->findOrFail($data['service_id']);
            $guard = app(WorkshopInvoiceGuard::class);
            if ($guard->isModernWorkshopService($service)) {
                throw ValidationException::withMessages([
                    'service_id' => 'Invoice Service modern harus dibuat melalui konversi Estimasi setelah pekerjaan dan QC selesai.',
                ]);
            }
            $guard->assertCanCreateServiceInvoice($service);
        }

        return DB::transaction(function () use ($data) {
            $data['invoice_number'] = $this->generateInvoiceNumber();
            $data['created_by'] = auth()->id() ?? 1;

            $items = $data['items'] ?? [];
            // Consume stock from the lowest product_id first so concurrent
            // invoices lock rows in a consistent order (deadlock prevention).
            usort($items, fn ($a, $b) => ($a['product_id'] ?? 0) <=> ($b['product_id'] ?? 0));

            // Calculate total from items (post line-discount)
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $this->lineTotal($item);
            }
            // Calculate discount from percent if needed
            $data['discount_type'] = $data['discount_type'] ?? 'fixed';
            if ($data['discount_type'] === 'percent' && ! empty($data['discount_percent'])) {
                $data['discount'] = round($totalAmount * ((float) $data['discount_percent'] / 100), 2);
            }

            $data['total_amount'] = round($totalAmount, 2);
            $data['grand_total'] = round($totalAmount + ($data['tax_amount'] ?? 0) - ($data['discount'] ?? 0), 2);

            $data['dp_status'] = 'none';
            $data['payment_status'] = 0;
            $data['paid_amount'] = 0;
            $data['amount_received'] = 0;

            $invoice = Invoice::create($data);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => $this->lineTotal($item),
                    'discount' => $item['discount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                    'warranty_expiry' => $item['warranty_expiry'] ?? null,
                    'sold_date' => $item['sold_date'] ?? null,
                ]);

                // Auto-reduce stock when product_id is linked
                if (! empty($item['product_id'])) {
                    StockService::decrement(
                        (int) $item['product_id'],
                        (float) ($item['quantity'] ?? 1),
                        'out',
                        'Invoice #'.$invoice->invoice_number,
                        Invoice::class,
                        $invoice->id,
                    );
                }
            }

            app(AutoJournalService::class)->journalInvoiceIssued($invoice);

            return $invoice;
        });
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $linkedService = $invoice->service;
        if ($invoice->invoice_type === 'service' && $invoice->service_id
            && $linkedService instanceof Service
            && app(WorkshopInvoiceGuard::class)->isModernWorkshopService($linkedService)) {
            throw ValidationException::withMessages([
                'service_id' => 'Invoice Service modern berasal dari scope Estimasi dan tidak dapat diubah melalui form generic.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $data) {
            abort_if((float) $invoice->paid_amount > 0 || $invoice->paymentRecords()->exists(), 403, 'Invoice yang sudah memiliki pembayaran tidak dapat mengubah data finansial.');

            $items = $data['items'] ?? [];
            unset($data['items']);

            $oldQuantities = [];
            foreach ($invoice->items as $oldItem) {
                if (! empty($oldItem->product_id)) {
                    $oldQuantities[(int) $oldItem->product_id] = ($oldQuantities[(int) $oldItem->product_id] ?? 0) + (float) $oldItem->quantity;
                }
            }

            $newQuantities = [];
            $totalAmount = 0;
            foreach ($items as $item) {
                $totalAmount += $this->lineTotal($item);
                if (! empty($item['product_id'])) {
                    $newQuantities[(int) $item['product_id']] = ($newQuantities[(int) $item['product_id']] ?? 0) + (float) ($item['quantity'] ?? 1);
                }
            }
            // Net delta per product (positive = additional consumption)
            $deltas = [];
            foreach ($newQuantities + [] as $pid => $qty) {
                $deltas[$pid] = $qty - ($oldQuantities[$pid] ?? 0);
            }
            foreach ($oldQuantities + [] as $pid => $qty) {
                if (! array_key_exists($pid, $deltas)) {
                    $deltas[$pid] = -$qty;
                }
            }

            $data['discount_type'] = $data['discount_type'] ?? ($invoice->discount_type ?? 'fixed');
            if ($data['discount_type'] === 'percent' && ! empty($data['discount_percent'])) {
                $data['discount'] = round($totalAmount * ((float) $data['discount_percent'] / 100), 2);
            }

            $data['total_amount'] = round($totalAmount, 2);
            $data['grand_total'] = round($totalAmount + ($data['tax_amount'] ?? 0) - ($data['discount'] ?? 0), 2);

            $data['dp_status'] = 'none';
            unset($data['payment_status'], $data['paid_amount'], $data['amount_received']);

            ksort($deltas); // deterministic lock order

            foreach ($deltas as $pid => $delta) {
                if ($delta < 0) {
                    StockService::increment($pid, abs($delta), 'in', 'Koreksi Invoice #'.$invoice->invoice_number, Invoice::class, $invoice->id);
                } elseif ($delta > 0) {
                    StockService::decrement($pid, $delta, 'out', 'Koreksi Invoice #'.$invoice->invoice_number, Invoice::class, $invoice->id);
                }
            }

            $invoice->update($data);

            if (! empty($items)) {
                $invoice->items()->delete();
                foreach ($items as $item) {
                    $invoice->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => $this->lineTotal($item),
                        'discount' => $item['discount'] ?? 0,
                        'discount_type' => $item['discount_type'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'warranty_expiry' => $item['warranty_expiry'] ?? null,
                        'sold_date' => $item['sold_date'] ?? null,
                    ]);
                }
            }

            // Re-align the posted accounting with the new financials (only runs
            // for unpaid invoices, which update() already guarantees).
            app(AutoJournalService::class)->realignInvoiceAccounting($invoice);

            return $invoice->fresh();
        });
    }

    public function generateInvoiceNumber(): string
    {
        $scheme = InvoiceScheme::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($scheme) {
            // Lock the scheme row so two concurrent invoices can never read
            // the same next_number.
            return DB::transaction(function () use ($scheme) {
                $scheme = InvoiceScheme::query()->whereKey($scheme->id)->lockForUpdate()->first();

                $number = (int) $scheme->next_number;
                $scheme->next_number = $number + 1;
                $scheme->save();

                $seq = str_pad((string) $number, 4, '0', STR_PAD_LEFT);
                $format = $scheme->format ?: ($scheme->prefix.'-{seq}');

                return str_replace(
                    ['{prefix}', '{year}', '{month}', '{seq}'],
                    [$scheme->prefix, date('Y'), date('m'), $seq],
                    $format
                );
            });
        }

        return DocumentNumberService::generate(DocumentNumberService::INVOICES, 'INV', 'Ym', 4);
    }

    public function calculateTotals(Invoice $invoice): void
    {
        $total = $invoice->items()->sum('total_price');
        $invoice->update([
            'total_amount' => round($total, 2),
            'grand_total' => round($total + ($invoice->tax_amount ?? 0) - ($invoice->discount ?? 0), 2),
        ]);
    }

    /**
     * Compute the line-level discount amount for a single item.
     */
    protected function lineDiscount(array $item): float
    {
        $subtotal = (float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0);
        $discount = (float) ($item['discount'] ?? 0);
        $type = $item['discount_type'] ?? null;

        if ($type === 'percent') {
            $discount = $subtotal * ($discount / 100);
        }

        return min(round($discount, 2), round($subtotal, 2));
    }

    /**
     * Line total after line-level discount: (unit_price * quantity) - discount.
     */
    protected function lineTotal(array $item): float
    {
        $subtotal = (float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0);

        return round($subtotal - $this->lineDiscount($item), 2);
    }

    public function deleteWithStockRestore(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            abort_if($invoice->paymentRecords()->exists(), 403, 'Invoice sudah memiliki pembayaran dan tidak bisa dihapus.');

            foreach ($invoice->items as $item) {
                if (! empty($item->product_id)) {
                    StockService::increment(
                        (int) $item->product_id,
                        (float) $item->quantity,
                        'in',
                        'Invoice #'.$invoice->invoice_number.' (batal)',
                        Invoice::class,
                        $invoice->id,
                    );
                }
            }

            app(AutoJournalService::class)->reverseJournalInvoiceIssued($invoice);

            $invoice->items()->delete();
            $invoice->delete();
        });

        ActivityLog::record('invoice.delete', null, "Invoice {$invoice->invoice_number} dibatalkan");
    }

    /**
     * Aggregate stock demand for a set of items (used by POS/Sale flows).
     *
     * @return array<int, float> product_id => total quantity
     */
    public static function demandByProduct(array $items): array
    {
        $demand = [];
        foreach ($items as $item) {
            if (! empty($item['product_id'])) {
                $demand[(int) $item['product_id']] = ($demand[(int) $item['product_id']] ?? 0) + (float) ($item['quantity'] ?? 1);
            }
        }
        ksort($demand);

        return $demand;
    }

    /** Legacy helper kept for API compatibility with older callers. */
    protected function validateStockAvailability(array $items): void
    {
        $productIds = array_filter(array_column($items, 'product_id'));
        if (empty($productIds)) {
            return;
        }

        $stockRecords = StockRecord::withoutGlobalScopes()
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        $products = Product::withoutGlobalScopes()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach (self::demandByProduct($items) as $pid => $qty) {
            $stock = $stockRecords[$pid] ?? null;
            if ($stock && $stock->quantity < $qty) {
                $name = $products[$pid]?->name ?? "ID {$pid}";
                throw new \RuntimeException("Stok \"{$name}\" tidak cukup: tersedia {$stock->quantity}, dibutuhkan {$qty}.");
            }
        }
    }
}
