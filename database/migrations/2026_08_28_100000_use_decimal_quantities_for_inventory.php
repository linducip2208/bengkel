<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_records', function (Blueprint $table) {
            $table->decimal('quantity', 15, 2)->default(0)->change();
            $table->decimal('minimum_stock', 15, 2)->default(0)->change();
        });

        Schema::table('stock_histories', function (Blueprint $table) {
            $table->decimal('quantity_change', 15, 2)->change();
            $table->decimal('previous_stock', 15, 2)->change();
            $table->decimal('new_stock', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_records', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
            $table->integer('minimum_stock')->default(0)->change();
        });

        Schema::table('stock_histories', function (Blueprint $table) {
            $table->integer('quantity_change')->change();
            $table->integer('previous_stock')->change();
            $table->integer('new_stock')->change();
        });
    }
};
