<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseOrderReceiptService
{
    public function receive(PurchaseOrder $purchaseOrder, array $requested = []): Purchase
    {
        return DB::transaction(function () use ($purchaseOrder, $requested) {
            $locked = PurchaseOrder::query()->whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['approved', 'partially_received'], true)) {
                throw new \RuntimeException('PO hanya dapat diterima setelah disetujui.');
            }

            $locked->load('items.product');
            /** @var Collection<int, PurchaseOrderItem> $items */
            $items = $locked->items;
            if ($items->contains(fn ($item) => ! $item->product_id)) {
                throw new \RuntimeException('Semua item penerimaan wajib terhubung ke produk.');
            }

            $requestedByItem = collect($requested)->keyBy(fn ($item) => (int) $item['purchase_order_item_id']);
            $receiptLines = [];
            foreach ($items->sortBy('product_id') as $item) {
                $remaining = round((float) $item->quantity - (float) $item->received_quantity, 2);
                $quantity = $requestedByItem->has($item->id)
                    ? round((float) $requestedByItem->get($item->id)['quantity'], 2)
                    : (empty($requested) ? $remaining : 0.0);
                if ($quantity < 0.01) {
                    continue;
                }
                /** @var Product $product */
                $product = $item->product;
                if ($quantity > $remaining) {
                    throw new \RuntimeException("Penerimaan {$product->name} melebihi sisa PO ({$remaining}).");
                }
                $receiptLines[] = [$item, $quantity];
            }
            if ($receiptLines === []) {
                throw new \RuntimeException('Tidak ada quantity tersisa yang dapat diterima.');
            }

            $total = round(collect($receiptLines)->sum(fn ($line) => $line[1] * (float) $line[0]->unit_price), 2);
            $purchase = Purchase::create([
                'purchase_no' => DocumentNumberService::generate(DocumentNumberService::PURCHASES, 'PO', 'Ymd', 4),
                'supplier_id' => $locked->supplier_id,
                'purchase_date' => now()->toDateString(),
                'status' => 'received',
                'total_amount' => $total,
                'notes' => 'Penerimaan dari purchase order #'.$locked->po_number,
                'created_by' => auth()->id(),
                'branch_id' => $locked->branch_id,
            ]);

            foreach ($receiptLines as [$item, $quantity]) {
                $purchase->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => round($quantity * (float) $item->unit_price, 2),
                ]);
                StockService::increment($item->product_id, $quantity, 'purchase', "Purchase #{$purchase->purchase_no}", Purchase::class, $purchase->id);
                $item->increment('received_quantity', $quantity);
            }

            $locked->refresh()->load('items');
            /** @var Collection<int, PurchaseOrderItem> $items */
            $items = $locked->items;
            $complete = $items->every(fn ($item) => round((float) $item->received_quantity, 2) >= round((float) $item->quantity, 2));
            $locked->update(['status' => $complete ? 'received' : 'partially_received']);
            app(AutoJournalService::class)->journalPurchase($purchase);

            return $purchase->fresh(['items.product', 'supplier']);
        });
    }
}
