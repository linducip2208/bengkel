<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\StockHistory;
use App\Models\StockRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
            $initialStock = round((float) ($data['initial_stock'] ?? $data['current_stock'] ?? 0), 2);
            $minimumStock = $data['minimum_stock'] ?? null;
            $rackLocation = $data['rack_location'] ?? null;
            unset($data['initial_stock'], $data['current_stock'], $data['minimum_stock'], $data['rack_location']);

            $data['product_no'] = $this->generateProductNo();
            $product = Product::create($data);

            StockRecord::create([
                'product_id' => $product->id,
                'supplier_id' => $data['supplier_id'] ?? null,
                // Bootstrap metadata only. StockService applies the opening
                // balance once and writes the matching StockHistory ledger.
                'quantity' => 0,
                'minimum_stock' => $minimumStock ?? 0,
                'rack_location' => $rackLocation,
            ]);

            if ($initialStock > 0) {
                StockService::increment($product->id, $initialStock, 'initial', 'Stok awal saat pembuatan produk');
            }

            return $product->load(['productType', 'unit', 'supplier', 'stockRecord']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $currentStock = array_key_exists('current_stock', $data)
                ? round((float) $data['current_stock'], 2)
                : null;
            $stockData = array_filter([
                'minimum_stock' => $data['minimum_stock'] ?? null,
                'rack_location' => $data['rack_location'] ?? null,
            ], fn ($value) => $value !== null);

            unset($data['current_stock'], $data['minimum_stock'], $data['rack_location']);
            $product->update($data);

            $currentQuantity = (float) ($product->stockRecord()
                ->withoutGlobalScopes()
                ->value('quantity') ?? 0);

            if (! empty($stockData)) {
                $product->stockRecord()->withoutGlobalScopes()->updateOrCreate(
                    ['product_id' => $product->id],
                    $stockData,
                );
            }

            if ($currentStock !== null && $currentStock !== round($currentQuantity, 2)) {
                StockService::set(
                    $product->id,
                    $currentStock,
                    'product_edit',
                    'Stok diubah melalui form edit produk',
                    Product::class,
                    $product->id,
                );
            }

            return $product->fresh(['productType', 'unit', 'supplier', 'stockRecord']);
        });
    }

    public function adjustStock(Product $product, int|float $quantity, string $reason): ?StockHistory
    {
        return StockService::adjust(
            $product->id,
            $quantity,
            $quantity >= 0 ? 'adjustment_add' : 'adjustment_reduce',
            $reason,
        );
    }

    public function setStock(Product $product, int|float $newStock, string $reason): float
    {
        return StockService::set($product->id, $newStock, 'opname', $reason);
    }

    public function useInService(Product $product, int|float $quantity, ?int $serviceId = null): void
    {
        StockService::decrement(
            $product->id,
            $quantity,
            'usage',
            'Digunakan dalam servis',
            $serviceId ? Service::class : null,
            $serviceId,
        );
    }

    public function getLowStock(): Collection
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
                        'cost_price' => ! empty($row['cost_price']) ? (float) str_replace(',', '', $row['cost_price']) : null,
                        'warranty' => $row['warranty'] ?? null,
                        'description' => $row['description'] ?? null,
                    ]);

                    StockRecord::create([
                        'product_id' => $product->id,
                        'supplier_id' => $row['supplier_id'] ?? null,
                        'quantity' => 0,
                        'minimum_stock' => (int) ($row['minimum_stock'] ?? 0),
                        'rack_location' => $row['rack_location'] ?? null,
                    ]);

                    $initialStock = round((float) ($row['initial_stock'] ?? $row['current_stock'] ?? 0), 2);
                    if ($initialStock > 0) {
                        StockService::increment(
                            $product->id,
                            $initialStock,
                            'initial',
                            'Stok awal dari import produk',
                        );
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'Baris '.($index + 1).': '.$e->getMessage();
                }
            }
        });

        return ['imported' => $imported, 'errors' => $errors];
    }

    private function generateProductNo(): string
    {
        return DocumentNumberService::generate(DocumentNumberService::PRODUCTS, 'PRD', 'Ym', 4);
    }
}
