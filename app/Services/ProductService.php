<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockHistory;
use App\Models\StockRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['productType', 'unit', 'supplier', 'stockRecord', 'reservations'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('product_no', 'like', "%{$search}%");
                });
            })
            ->when($filters['product_type_id'] ?? null, function ($q, $typeId) {
                $q->where('product_type_id', $typeId);
            })
            ->when($filters['supplier_id'] ?? null, function ($q, $supplierId) {
                $q->where('supplier_id', $supplierId);
            })
            ->when($filters['stock_status'] ?? null, function ($q, $status) {
                $q->whereHas('stockRecord', function ($q) use ($status) {
                    if ($status === 'out') {
                        $q->where('quantity', '<=', 0);
                    } elseif ($status === 'low') {
                        $q->whereColumn('quantity', '<=', 'minimum_stock')
                            ->where('quantity', '>', 0);
                    } elseif ($status === 'in_stock') {
                        $q->where('quantity', '>', 0)
                            ->where(function ($q) {
                                $q->whereColumn('quantity', '>', 'minimum_stock')
                                    ->orWhereNull('minimum_stock');
                            });
                    }
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $initialStock = $data['initial_stock'] ?? 0;
            $minimumStock = $data['minimum_stock'] ?? null;
            $rackLocation = $data['rack_location'] ?? null;
            unset($data['initial_stock'], $data['minimum_stock'], $data['rack_location']);

            $data['product_no'] = $this->generateProductNo();
            $product = Product::create($data);

            StockRecord::create([
                'product_id' => $product->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                'quantity' => $initialStock,
                'minimum_stock' => $minimumStock ?? 0,
                'rack_location' => $rackLocation,
            ]);

            if ($initialStock > 0) {
                $this->recordStockHistory($product, $initialStock, 0, $initialStock, 'initial', 'Stok awal saat pembuatan produk');
            }

            return $product->load(['productType', 'unit', 'supplier', 'stockRecord']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        $stockData = [];
        if (isset($data['minimum_stock'])) {
            $stockData['minimum_stock'] = $data['minimum_stock'];
            unset($data['minimum_stock']);
        }
        if (isset($data['rack_location'])) {
            $stockData['rack_location'] = $data['rack_location'];
            unset($data['rack_location']);
        }

        $product->update($data);

        if (!empty($stockData) && $product->stockRecord) {
            $product->stockRecord->update($stockData);
        }

        return $product->fresh(['productType', 'unit', 'supplier', 'stockRecord']);
    }

    public function adjustStock(Product $product, int $quantity, string $reason): StockHistory
    {
        return DB::transaction(function () use ($product, $quantity, $reason) {
            $stockRecord = $this->getOrCreateStockRecord($product);
            $previousStock = $stockRecord->quantity;
            $newStock = $previousStock + $quantity;

            $stockRecord->update(['quantity' => $newStock]);

            $type = $quantity > 0 ? 'adjustment_add' : 'adjustment_reduce';

            return $this->recordStockHistory($product, $quantity, $previousStock, $newStock, $type, $reason);
        });
    }

    public function setStock(Product $product, int $newStock, string $reason): StockHistory
    {
        return DB::transaction(function () use ($product, $newStock, $reason) {
            $stockRecord = $this->getOrCreateStockRecord($product);
            $previousStock = $stockRecord->quantity;
            $quantityChange = $newStock - $previousStock;

            $stockRecord->update(['quantity' => $newStock]);

            return $this->recordStockHistory($product, $quantityChange, $previousStock, $newStock, 'opname', $reason);
        });
    }

    public function useInService(Product $product, int $quantity, ?int $serviceId = null): void
    {
        DB::transaction(function () use ($product, $quantity, $serviceId) {
            $stockRecord = $this->getOrCreateStockRecord($product);

            if ($stockRecord->quantity < $quantity) {
                throw new \RuntimeException("Stok \"{$product->name}\" tidak cukup: tersedia {$stockRecord->quantity}, dibutuhkan {$quantity}.");
            }

            $previousStock = $stockRecord->quantity;
            $newStock = $previousStock - $quantity;

            $stockRecord->update(['quantity' => $newStock]);

            $this->recordStockHistory(
                $product, -$quantity, $previousStock, $newStock, 'usage',
                'Digunakan dalam servis',
                $serviceId ? \App\Models\Service::class : null,
                $serviceId
            );
        });
    }

    public function getLowStock(): \Illuminate\Support\Collection
    {
        return Product::whereHas('stockRecord', function ($q) {
            $q->whereColumn('quantity', '<=', 'minimum_stock');
        })->with(['productType', 'unit', 'stockRecord'])->get();
    }

    public function bulkImport(array $rows): array
    {
        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$imported, &$errors) {
            foreach ($rows as $index => $row) {
                try {
                    $product = Product::create([
                        'product_no' => $this->generateProductNo(),
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'product_type_id' => $row['product_type_id'],
                        'unit_id' => $row['unit_id'],
                        'supplier_id' => $row['supplier_id'] ?? null,
                        'price' => (float) str_replace(',', '', $row['price']),
                        'cost_price' => !empty($row['cost_price']) ? (float) str_replace(',', '', $row['cost_price']) : null,
                        'warranty' => $row['warranty'] ?? null,
                        'description' => $row['description'] ?? null,
                    ]);

                    StockRecord::create([
                        'product_id' => $product->id,
                        'supplier_id' => $row['supplier_id'] ?? null,
                        'quantity' => (int) ($row['current_stock'] ?? 0),
                        'minimum_stock' => (int) ($row['minimum_stock'] ?? 0),
                        'rack_location' => $row['rack_location'] ?? null,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
                }
            }
        });

        return ['imported' => $imported, 'errors' => $errors];
    }

    private function getOrCreateStockRecord(Product $product): StockRecord
    {
        if ($product->stockRecord) {
            return $product->stockRecord;
        }

        return StockRecord::create([
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id,
            'quantity' => 0,
            'minimum_stock' => 0,
            'rack_location' => null,
        ]);
    }

    private function recordStockHistory(
        Product $product,
        int $quantityChange,
        int $previousStock,
        int $newStock,
        string $type,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockHistory {
        return StockHistory::create([
            'product_id' => $product->id,
            'quantity_change' => $quantityChange,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'type' => $type,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => auth()->id(),
        ]);
    }

    private function generateProductNo(): string
    {
        $prefix = 'PRD-' . date('Ym');
        $last = Product::withTrashed()
            ->where('product_no', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();
        $next = $last ? (int) substr($last->product_no, -4) + 1 : 1;

        return $prefix . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}
