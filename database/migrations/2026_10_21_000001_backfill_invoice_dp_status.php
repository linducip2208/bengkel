<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill status untuk invoice yang sudah punya dp_amount sebelum
 * DP ikut mengurangi sisa tagihan.
 *
 * - dp_status: none → dp_paid / full_paid sesuai nominal DP.
 * - payment_status: dihitung ulang dari collected (paid + DP)
 *   agar badge/filter "Sebagian/Lunas" konsisten dengan sisa baru.
 *
 * Hanya menyentuh baris dengan dp_amount > 0. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'dp_amount')) {
            return;
        }

        DB::table('invoices')->where('dp_amount', '>', 0)->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $grand = (float) $row->grand_total;
                $paid = (float) ($row->paid_amount ?? 0);
                $dp = max(0, (float) $row->dp_amount);
                $collected = round($paid + $dp, 2);

                $dpStatus = 'dp_paid';
                if ($dp <= 0.009) {
                    $dpStatus = 'none';
                } elseif ($grand > 0 && $dp >= $grand - 0.009) {
                    $dpStatus = 'full_paid';
                }

                $paymentStatus = 0;
                if ($grand > 0 && $collected >= $grand - 0.009) {
                    $paymentStatus = 2;
                } elseif ($collected > 0.009) {
                    $paymentStatus = 1;
                }

                DB::table('invoices')->where('id', $row->id)->update([
                    'dp_status' => $dpStatus,
                    'payment_status' => $paymentStatus,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Data backfill — tidak di-rollback otomatis agar status
        // yang sudah benar tidak rusak saat rollback.
    }
};
