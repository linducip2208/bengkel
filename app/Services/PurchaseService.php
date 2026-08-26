<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseHistoryRecord;
use App\Models\PurchaseItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Purchase::query()
            ->with(['supplier', 'items'])
            ->when($filters['status'] ?? null, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['supplier_id'] ?? null, function ($q, $supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->when($filters['date_from'] ?? null, function ($q, $dateFrom) {
                $q->whereDate('purchase_date', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($q, $dateTo) {
                $q->whereDate('purchase_date', '<=', $dateTo);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('purchase_no', 'like', "%{$search}%");
            })
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $totalAmount = 0;

            $purchase = Purchase::create([
                'purchase_no' => $this->generatePurchaseNo(),
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'status' => $data['status'] ?? 'ordered',
                'notes' => $data['notes'] ?? null,
                'total_amount' => 0,
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $totalPrice = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $totalAmount += $totalPrice;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $purchase->update(['total_amount' => $totalAmount]);

            PurchaseHistoryRecord::create([
                'purchase_id' => $purchase->id,
                'status' => $data['status'] ?? 'ordered',
                'notes' => 'Purchase order dibuat',
                'changed_at' => now(),
            ]);

            return $purchase->fresh(['items', 'supplier']);
        });
    }

    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {
            $totalAmount = 0;

            $purchase->update([
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $purchase->items()->delete();

            foreach ($data['items'] as $item) {
                $totalPrice = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $totalAmount += $totalPrice;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $purchase->update(['total_amount' => $totalAmount]);

            return $purchase->fresh(['items', 'supplier']);
        });
    }

    public function markReceived(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            // Status guard INSIDE the transaction + row lock: a concurrent
            // double-click re-reads the authoritative status and aborts.
            $locked = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->first();
            if ($locked->status !== 'ordered') {
                throw new \RuntimeException('Hanya purchase order dengan status "Dipesan" yang dapat diterima.');
            }

            foreach ($locked->items as $item) {
                StockService::increment(
                    $item->product_id,
                    (float) $item->quantity,
                    'purchase',
                    "PO #{$locked->purchase_no}",
                    Purchase::class,
                    $locked->id,
                );
            }

            $locked->update(['status' => 'received']);

            try {
                app(AutoJournalService::class)->journalPurchase($locked);
            } catch (\Throwable $e) {
                \Log::warning("AutoJournal purchase: {$e->getMessage()}");
            }

            PurchaseHistoryRecord::create([
                'purchase_id' => $locked->id,
                'status' => 'received',
                'notes' => 'Barang diterima, stok ditambahkan',
                'changed_at' => now(),
            ]);

            return $locked->fresh(['items.product', 'supplier']);
        });
    }

    public function calculateTotal(Purchase $purchase): float
    {
        return $purchase->total_amount;
    }

    public function getPendingOrders(): Collection
    {
        return Purchase::where('status', 'ordered')
            ->with(['supplier', 'items.product'])
            ->orderByDesc('purchase_date')
            ->get();
    }

    private function generatePurchaseNo(): string
    {
        return DocumentNumberService::generate(DocumentNumberService::PURCHASES, 'PO', 'Ymd', 4);
    }
}
