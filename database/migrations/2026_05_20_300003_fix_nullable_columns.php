<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Reconcile schema with form validators: columns marked `nullable` in FormRequest
 * but NOT NULL in DB cause silent runtime errors when callers omit them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $changes = [
            'colors' => ['hex_code VARCHAR(50) NULL'],
            'incomes' => [
                'payment_method_id BIGINT UNSIGNED NULL',
                'customer_id BIGINT UNSIGNED NULL',
                'invoice_number VARCHAR(255) NULL',
            ],
            'product_units' => ['abbreviation VARCHAR(50) NULL'],
            'vehicles' => [
                'vehicle_type_id BIGINT UNSIGNED NULL',
                'vehicle_brand_id BIGINT UNSIGNED NULL',
                'fuel_type_id BIGINT UNSIGNED NULL',
                'number_plate VARCHAR(20) NULL',
            ],
        ];

        foreach ($changes as $table => $colDefs) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($colDefs as $colDef) {
                $colName = explode(' ', $colDef)[0];
                if (! Schema::hasColumn($table, $colName)) {
                    continue;
                }
                try {
                    DB::statement("ALTER TABLE `$table` MODIFY $colDef");
                } catch (Throwable $e) {
                    Log::warning("Migration could not modify $table.$colName: ".$e->getMessage());
                }
            }
        }
    }

    public function down(): void
    {
        // Not reversed — making columns nullable is non-destructive.
    }
};
