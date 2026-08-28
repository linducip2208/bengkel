<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class SaleService extends BaseService
{
    /**
     * Create a sale together with its items, decrementing stock atomically.
     *
     * @param  array  $data  sale attributes, may include 'items'
     */
    public function create(array $data): Sale
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['sales_no'] = $this->generateSalesNo();
        $data['created_by'] = auth()->id() ?? 1;
        $data['total_amount'] = $this->lineItemsTotal($items);
        $data['tax_amount'] = $data['tax_amount'] ?? 0;
        $data['grand_total'] = round((float) $data['total_amount'] + (float) $data['tax_amount'], 2);

        return DB::transaction(function () use ($data, $items) {
            $sale = Sale::create($data);
            $this->syncItems($sale, $items);

            return $sale;
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        if (! empty($items)) {
            $data['total_amount'] = $this->lineItemsTotal($items);
            $data['tax_amount'] = $data['tax_amount'] ?? $sale->tax_amount;
            $data['grand_total'] = round((float) $data['total_amount'] + (float) $data['tax_amount'], 2);
        } else {
            $data['grand_total'] = round((float) ($data['total_amount'] ?? $sale->total_amount) + (float) ($data['tax_amount'] ?? $sale->tax_amount), 2);
        }

        return DB::transaction(function () use ($sale, $data, $items) {
            $sale->update($data);
            if (! empty($items)) {
                $this->syncItems($sale, $items, true);
            }

            return $sale->fresh('items');
        });
    }

    /**
     * Reversal-style delete: restore stock. Hard delete is only allowed for
     * sales that have not generated invoices/payments/journals; otherwise the
     * caller should route through cancellation.
     */
    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            if ($sale->invoices()->exists()) {
                throw new \RuntimeException('Penjualan sudah memiliki invoice — batalkan/void invoice terlebih dahulu.');
            }

            foreach ($sale->items as $old) {
                /** @var SaleItem $old */
                StockService::increment(
                    (int) $old->product_id,
                    (float) $old->quantity,
                    'sale_restore',
                    'Koreksi penjualan '.$sale->sales_no,
                    Sale::class,
                    $sale->id,
                );
            }
            $sale->items()->delete();
            $sale->delete();
        });
    }

    public function generateSalesNo(): string
    {
        return DocumentNumberService::generate(DocumentNumberService::SALES, 'SLS', 'Ym', 4);
    }

    protected function lineItemsTotal(array $items): float
    {
        return round((float) collect($items)->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_price']), 2);
    }

    protected function syncItems(Sale $sale, array $items, bool $isUpdate = false): void
    {
        if ($isUpdate) {
            foreach ($sale->items as $old) {
                /** @var SaleItem $old */
                StockService::increment(
                    (int) $old->product_id,
                    (float) $old->quantity,
                    'sale_restore',
                    'Koreksi penjualan '.$sale->sales_no,
                    Sale::class,
                    $sale->id,
                );
            }
            $sale->items()->delete();
        }

        usort($items, fn ($a, $b) => $a['product_id'] <=> $b['product_id']);

        foreach ($items as $item) {
            $quantity = round((float) $item['quantity'], 2);
            $unitPrice = round((float) $item['unit_price'], 2);
            if ($quantity <= 0) {
                throw new \RuntimeException('Quantity item penjualan harus lebih besar dari nol.');
            }

            SaleItem::create([
                'sale_id' => $sale->id,
                'product_id' => $item['product_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => round($quantity * $unitPrice, 2),
            ]);

            StockService::decrement(
                (int) $item['product_id'],
                $quantity,
                'sale',
                'Penjualan '.$sale->sales_no,
                Sale::class,
                $sale->id,
            );
        }
    }
}
