<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->timestamps();

            $table->index('sale_id');
            $table->index('product_id');
        });

        // Spare part sales support walk-in customers → customer_id becomes nullable.
        if (Schema::hasColumn('sales', 'customer_id')) {
            try {
                DB::statement('ALTER TABLE `sales` MODIFY `customer_id` BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('could not modify sales.customer_id: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
