<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve the total on-hand quantity if legacy data contains more
        // than one row, then retain one canonical row per product.
        DB::table('stock_records')
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('product_id')
            ->each(function ($duplicate): void {
                $records = DB::table('stock_records')
                    ->where('product_id', $duplicate->product_id)
                    ->orderBy('id')
                    ->get();

                $canonical = $records->first();
                DB::table('stock_records')->where('id', $canonical->id)->update([
                    'quantity' => $records->sum(fn ($record) => (float) $record->quantity),
                    'updated_at' => now(),
                ]);

                DB::table('stock_records')
                    ->where('product_id', $duplicate->product_id)
                    ->where('id', '<>', $canonical->id)
                    ->delete();
            });

        Schema::table('stock_records', function (Blueprint $table): void {
            $table->unique('product_id', 'stock_records_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_records', function (Blueprint $table): void {
            $table->dropUnique('stock_records_product_id_unique');
        });
    }
};
