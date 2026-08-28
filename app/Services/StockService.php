<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockHistory;
use App\Models\StockRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Single choke-point for every stock mutation.
 *
 * Guarantees under concurrent requests:
 *  - The StockRecord row is locked (SELECT ... FOR UPDATE) before read and
 *    held until the surrounding transaction commits — no lost updates.
 *  - previous_stock / new_stock ledger snapshots are always computed from
 *    the locked row, never from a stale read.
 *  - Decrement refuses to take stock below zero unless config('stock.allow_negative')
 *    is explicitly enabled, so two concurrent consumers can never both spend
 *    the same available unit ("oversell").
 *
 * Must be called inside (or will open) a DB transaction. When nested inside
 * a caller's transaction Laravel uses savepoints, and the row lock is held
 * until the OUTER commit — which is exactly what makes multi-line operations
 * like POS checkout atomic.
 */
class StockService
{
    /**
     * Take stock out of inventory. Throws RuntimeException when insufficient
     * (unless negative stock is explicitly allowed by configuration).
     */
    public static function decrement(
        int $productId,
        int|float $quantity,
        string $type,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockHistory {
        $quantity = self::normalize($quantity);

        return DB::transaction(function () use ($productId, $quantity, $type, $reason, $referenceType, $referenceId) {
            $record = self::lockOrCreate($productId);
            $previous = self::normalize($record->quantity);
            $newStock = $previous - $quantity;

            if ($newStock < 0 && ! config('stock.allow_negative', false)) {
                $name = Product::withoutGlobalScopes()->find($productId)?->name ?? "ID {$productId}";

                throw new \RuntimeException(
                    "Stok \"{$name}\" tidak cukup: tersedia {$previous}, dibutuhkan {$quantity}."
                );
            }

            $record->quantity = $newStock;
            $record->save();

            return self::history($productId, -$quantity, $previous, $newStock, $type, $reason, $referenceType, $referenceId);
        });
    }

    /** Add stock back into inventory. */
    public static function increment(
        int $productId,
        int|float $quantity,
        string $type,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockHistory {
        $quantity = self::normalize($quantity);

        return DB::transaction(function () use ($productId, $quantity, $type, $reason, $referenceType, $referenceId) {
            $record = self::lockOrCreate($productId);
            $previous = self::normalize($record->quantity);
            $newStock = $previous + $quantity;

            $record->quantity = $newStock;
            $record->save();

            return self::history($productId, $quantity, $previous, $newStock, $type, $reason, $referenceType, $referenceId);
        });
    }

    /**
     * Opname-style absolute set. Returns the applied delta.
     */
    public static function set(
        int $productId,
        int|float $newStock,
        string $type,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): float {
        $newStock = self::normalize($newStock);

        return (float) DB::transaction(function () use ($productId, $newStock, $type, $reason, $referenceType, $referenceId) {
            $record = self::lockOrCreate($productId);
            $previous = self::normalize($record->quantity);
            $delta = $newStock - $previous;

            if (abs($delta) < 0.005) {
                return 0;
            }

            $record->quantity = $newStock;
            $record->save();

            self::history($productId, $delta, $previous, $newStock, $type, $reason, $referenceType, $referenceId);

            return $delta;
        });
    }

    /**
     * Apply a relative +/- adjustment with sufficiency enforcement for
     * negative deltas.
     */
    public static function adjust(
        int $productId,
        int|float $delta,
        string $type,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): ?StockHistory {
        $delta = self::normalizeSigned($delta);
        if ($delta === 0) {
            return null;
        }

        return $delta > 0
            ? self::increment($productId, $delta, $type, $reason, $referenceType, $referenceId)
            : self::decrement($productId, abs($delta), $type, $reason, $referenceType, $referenceId);
    }

    /**
     * Lock the record row (or create it when missing). The retry loop handles
     * the create race: the UNIQUE(product_id) index added in the integrity
     * migration turns a double-insert into a caught exception instead of two
     * divergent rows.
     */
    private static function lockOrCreate(int $productId): StockRecord
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $record = StockRecord::withoutGlobalScopes()
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if ($record) {
                return $record;
            }

            try {
                StockRecord::withoutGlobalScopes()->create([
                    'product_id' => $productId,
                    'supplier_id' => Product::withoutGlobalScopes()->find($productId)?->supplier_id,
                    'quantity' => 0,
                    'minimum_stock' => 0,
                ]);
            } catch (UniqueConstraintViolationException) {
                // Another request created it first — loop and lock it.
            }
        }

        throw new \RuntimeException("Gagal menyiapkan stok untuk produk #{$productId}.");
    }

    private static function history(
        int $productId,
        int|float $change,
        int|float $previous,
        int|float $new,
        string $type,
        ?string $reason,
        ?string $referenceType,
        ?int $referenceId,
    ): StockHistory {
        return StockHistory::create([
            'product_id' => $productId,
            'quantity_change' => $change,
            'previous_stock' => $previous,
            'new_stock' => $new,
            'type' => $type,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'user_id' => auth()->id(),
        ]);
    }

    private static function normalize(int|float $value): float
    {
        $value = round((float) $value, 2);

        if ($value < 0) {
            throw new \InvalidArgumentException('Quantity tidak boleh negatif.');
        }

        return $value;
    }

    private static function normalizeSigned(int|float $value): float
    {
        return round((float) $value, 2);
    }
}
