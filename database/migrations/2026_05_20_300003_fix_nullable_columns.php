<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconcile schema with form validators: columns marked `nullable` in FormRequest
 * but NOT NULL in DB cause silent runtime errors when callers omit them.
 *
 * Uses the schema builder (portable across MySQL and SQLite) instead of raw
 * `ALTER TABLE ... MODIFY`, which is MySQL-only and fails silently elsewhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('colors')) {
            Schema::table('colors', function (Blueprint $table) {
                $table->string('hex_code', 50)->nullable()->change();
            });
        }

        if (Schema::hasTable('incomes')) {
            Schema::table('incomes', function (Blueprint $table) {
                $table->unsignedBigInteger('payment_method_id')->nullable()->change();
                $table->unsignedBigInteger('customer_id')->nullable()->change();
                $table->string('invoice_number')->nullable()->change();
            });
        }

        if (Schema::hasTable('product_units')) {
            Schema::table('product_units', function (Blueprint $table) {
                $table->string('abbreviation', 50)->nullable()->change();
            });
        }

        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->unsignedBigInteger('vehicle_type_id')->nullable()->change();
                $table->unsignedBigInteger('vehicle_brand_id')->nullable()->change();
                $table->unsignedBigInteger('fuel_type_id')->nullable()->change();
                $table->string('number_plate', 20)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Not reversed — making columns nullable is non-destructive.
    }
};
