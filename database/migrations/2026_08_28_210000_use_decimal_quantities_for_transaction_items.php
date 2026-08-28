<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', fn (Blueprint $table) => $table->decimal('quantity', 15, 2)->default(0)->change());
        Schema::table('sale_items', fn (Blueprint $table) => $table->decimal('quantity', 15, 2)->default(0)->change());
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->decimal('previous_quantity', 15, 2)->change();
            $table->decimal('new_quantity', 15, 2)->change();
            $table->decimal('quantity_change', 15, 2)->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Irreversible migration: converting decimal inventory quantities back to integers would truncate stock data.');
    }
};
