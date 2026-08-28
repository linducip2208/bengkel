<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\StockHistory;
use App\Models\StockRecord;
use App\Services\StockService;
use Illuminate\Console\Command;

class AuditInitialStock extends Command
{
    protected $signature = 'inventory:audit-initial-stock {--fix : Terapkan perbaikan via StockService::set (default: dry-run)}';

    protected $description = 'Audit konsistensi stok: stok record vs ledger StockHistory & kelengkapan stok awal (opening balance)';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');

        $products = Product::withoutGlobalScopes()
            ->with('stockRecord')
            ->get();

        if ($products->isEmpty()) {
            $this->info('Tidak ada produk untuk diaudit.');

            return self::SUCCESS;
        }

        $productIds = $products->pluck('id');
        $ledgerTotals = StockHistory::withoutGlobalScopes()
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(quantity_change) as total')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $initialProducts = StockHistory::withoutGlobalScopes()
            ->whereIn('product_id', $productIds)
            ->where('type', 'initial')
            ->pluck('product_id')
            ->flip();

        $issues = 0;
        $fixed = 0;

        foreach ($products as $product) {
            /** @var StockRecord|null $stockRecord */
            $stockRecord = $product->stockRecord;
            /** @var object{total: int|float}|null $ledgerRow */
            $ledgerRow = $ledgerTotals->get($product->id);
            $record = (float) (optional($stockRecord)->quantity ?? 0);
            $ledger = (float) (optional($ledgerRow)->total ?? 0);
            $hasInitialHistory = $initialProducts->has($product->id);

            if ($record != $ledger) {
                $issues++;
                $mode = $fix ? 'FIX' : 'DRY';
                $this->line("  [{$mode}] #{$product->id} {$product->name}: record={$record} vs history={$ledger}");

                if ($fix) {
                    StockService::set(
                        $product->id,
                        $ledger,
                        'inventory.audit',
                        'Audit stok: sinkron stok record dengan ledger StockHistory',
                    );
                    $fixed++;
                }
            } elseif ($record > 0 && ! $hasInitialHistory) {
                $issues++;
                $mode = $fix ? 'FIX' : 'DRY';
                $this->line("  [{$mode}] #{$product->id} {$product->name}: stok {$record} tetapi opening balance (initial) belum tercatat di ledger.");

                if ($fix) {
                    StockService::set(
                        $product->id,
                        $record,
                        'inventory.audit',
                        'Audit stok: catat stok awal (opening balance)',
                    );
                    $fixed++;
                }
            }
        }

        if ($issues === 0) {
            $this->info("Audit selesai: semua {$products->count()} produk konsisten dengan ledger.");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Audit selesai: %d ketidakkonsistenan ditemukan, %d diperbaiki%s.',
            $issues,
            $fixed,
            $fix ? '' : ' (jalankan dengan --fix untuk menerapkan perbaikan)'
        ));

        return $fix ? self::SUCCESS : self::FAILURE;
    }
}
