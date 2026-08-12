<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseHistoryRecord;
use App\Models\PurchaseItem;
use App\Models\StockHistory;
use App\Models\StockRecord;
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
                $totalPrice = $item['quantity'] * $item['unit_price'];
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
                $totalPrice = $item['quantity'] * $item['unit_price'];
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
        if ($purchase->status !== 'ordered') {
            throw new \RuntimeException('Hanya purchase order dengan status "Dipesan" yang dapat diterima.');
        }

        return DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                $product = $item->product;

                $stockRecord = StockRecord::firstOrCreate(
                    ['product_id' => $product->id],
                    [
                        'supplier_id' => $product->supplier_id,
                        'quantity' => 0,
                        'minimum_stock' => 0,
                        'rack_location' => null,
                    ]
                );

                $previousStock = $stockRecord->quantity;
                $newStock = $previousStock + $item->quantity;

                $stockRecord->update(['quantity' => $newStock]);

                StockHistory::create([
                    'product_id' => $product->id,
                    'quantity_change' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'type' => 'purchase',
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'reason' => "PO #{$purchase->purchase_no}",
                    'user_id' => auth()->id(),
                ]);
            }

            $purchase->update(['status' => 'received']);

            try { app(AutoJournalService::class)->journalPurchase($purchase); } catch (\Throwable $e) { \Log::warning("AutoJournal purchase: {$e->getMessage()}"); }

            PurchaseHistoryRecord::create([
                'purchase_id' => $purchase->id,
                'status' => 'received',
                'notes' => 'Barang diterima, stok ditambahkan',
                'changed_at' => now(),
            ]);

            return $purchase->fresh(['items.product', 'supplier']);
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
        $prefix = 'PO-' . date('Ymd');
        $last = Purchase::withTrashed()->where('purchase_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();
        $next = $last ? (int) substr($last->purchase_no, -4) + 1 : 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
