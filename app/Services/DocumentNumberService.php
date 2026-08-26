<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Transaction-safe document numbering.
 *
 * Every generator in the codebase funnels through here instead of the old
 * "SELECT last number → +1 → exists()" pattern, which loses numbers under
 * concurrent requests. The counter row is locked with FOR UPDATE inside a
 * transaction (savepoint-safe when nested), so two concurrent callers can
 * never receive the same sequence value. Backed by UNIQUE indexes on each
 * document-number column as a final safety net.
 */
class DocumentNumberService
{
    /** Reserved keys — see database/migrations/*_create_document_sequences_table */
    public const PRODUCTS = 'products';

    public const INVOICES = 'invoices';

    public const POS_INVOICES = 'pos_invoices';

    public const SALES = 'sales';

    public const PURCHASES = 'purchases';

    public const PURCHASE_ORDERS = 'purchase_orders';

    public const REQUISITIONS = 'requisitions';

    public const SERVICES = 'services';

    public const STOCK_TRANSFERS = 'stock_transfers';

    public const GATE_PASSES = 'gate_passes';

    public const JOURNALS = 'journals';

    public const SUPPLIER_CLAIMS = 'supplier_claims';

    public const INSURANCE_CLAIMS = 'insurance_claims';

    public const SELL_RETURNS = 'sell_returns';

    /**
     * Reserve the next integer for a key. Safe to call inside an existing
     * transaction (uses a savepoint) or standalone.
     */
    public static function next(string $key): int
    {
        return (int) DB::transaction(function () use ($key) {
            DB::table('document_sequences')->insertOrIgnore([
                'key' => $key,
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('document_sequences')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            $value = (int) $row->value + 1;

            DB::table('document_sequences')
                ->where('key', $key)
                ->update(['value' => $value, 'updated_at' => now()]);

            return $value;
        });
    }

    /**
     * Build a formatted document number: {PREFIX}-{DATE}-{SEQ}.
     * e.g. generate(self::INVOICES, 'INV', 'Ym', 4) => INV-202608-0042
     */
    public static function generate(string $key, string $prefix, string $dateFormat = 'Ymd', int $pad = 4): string
    {
        $seq = self::next($key);

        return sprintf('%s-%s-%s', $prefix, now()->format($dateFormat), str_pad((string) $seq, $pad, '0', STR_PAD_LEFT));
    }

    /** Preview only — does not consume a number (for "will look like" hints). */
    public static function peek(string $key, string $prefix, string $dateFormat = 'Ymd', int $pad = 4): string
    {
        $current = (int) (DB::table('document_sequences')->where('key', $key)->value('value') ?? 0);

        return sprintf('%s-%s-%s', $prefix, now()->format($dateFormat), str_pad((string) ($current + 1), $pad, '0', STR_PAD_LEFT));
    }
}
