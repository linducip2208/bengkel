<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductUnit;
use App\Models\StockRecord;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class ProductsImport implements SkipsOnError, ToModel, WithHeadingRow
{
    use RemembersRowNumber;

    public int $imported = 0;

    public array $errors = [];

    public function model(array $row)
    {
        $code = trim((string) ($row['code'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));

        if ($code === '' || $name === '') {
            return null;
        }

        try {
            $data = [
                'name' => $name,
                'barcode' => $this->nullableString($row['barcode'] ?? null),
                'product_type_id' => $this->resolveProductType($row),
                'unit_id' => $this->resolveUnit($row),
                'cost_price' => $this->nullableFloat($row['cost_price'] ?? null),
                'price' => $this->float($row['price'] ?? 0),
                'warranty' => $this->nullableString($row['warranty'] ?? null),
                'description' => $this->nullableString($row['description'] ?? null),
            ];

            $product = Product::withoutGlobalScopes()->firstOrCreate(
                ['code' => $code],
                $data + ['product_no' => $this->generateProductNo()]
            );

            if (! $product->wasRecentlyCreated) {
                $product->update($data);
            }

            $minimumStock = $this->nullableInt($row['minimum_stock'] ?? null);
            $stockRecord = StockRecord::withoutGlobalScopes()->firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 0, 'minimum_stock' => $minimumStock ?? 0],
            );

            if ($minimumStock !== null && (int) $stockRecord->minimum_stock !== $minimumStock) {
                $stockRecord->update(['minimum_stock' => $minimumStock]);
            }

            // Stock columns are initial values only. Re-importing an existing
            // product must never add the same opening balance a second time.
            if ($product->wasRecentlyCreated) {
                $initialStock = $this->nullableFloat($row['initial_stock'] ?? $row['current_stock'] ?? null) ?? 0;
                if ($initialStock > 0) {
                    StockService::increment(
                        $product->id,
                        $initialStock,
                        'initial',
                        'Stok awal dari import produk',
                    );
                }
            }

            $this->imported++;

            return $product;
        } catch (Throwable $e) {
            $this->errors[] = 'Baris '.($this->getRowNumber() ?? '?').': '.$e->getMessage();

            return null;
        }
    }

    public function onError(Throwable $e)
    {
        $this->errors[] = 'Baris '.($this->getRowNumber() ?? '?').': '.$e->getMessage();
    }

    protected function resolveProductType(array $row): ?int
    {
        $value = $row['product_type_id'] ?? $row['product_type'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ProductType::where('id', (int) $value)->exists() ? (int) $value : null;
        }

        $type = ProductType::where('type', trim((string) $value))
            ->orWhere('slug', trim((string) $value))
            ->first();

        return $type?->id;
    }

    protected function resolveUnit(array $row): ?int
    {
        $value = $row['unit_id'] ?? $row['unit'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ProductUnit::where('id', (int) $value)->exists() ? (int) $value : null;
        }

        $unit = ProductUnit::where('name', trim((string) $value))
            ->orWhere('abbreviation', trim((string) $value))
            ->first();

        return $unit?->id;
    }

    protected function float($value): float
    {
        return (float) $this->nullableFloat($value);
    }

    protected function nullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace([',', ' '], ['', ''], (string) $value);

        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $clean = str_replace([',', ' '], ['', ''], (string) $value);

        return is_numeric($clean) ? (int) $clean : null;
    }

    protected function nullableString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    protected function generateProductNo(): string
    {
        return DocumentNumberService::generate(DocumentNumberService::PRODUCTS, 'PRD', 'Ym', 4);
    }
}
