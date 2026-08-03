<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reconcile remaining columns where inline validators say `nullable`
 * but DB still enforces NOT NULL. Found by scripts/check_nullable_inline.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        $changes = [
            'sales'                     => ['vehicle_id BIGINT UNSIGNED NULL'],
            'services'                  => ['repair_category_id BIGINT UNSIGNED NULL'],
            'countries'                 => ['code VARCHAR(50) NULL', 'phone_code VARCHAR(50) NULL'],
            'currencies'                => ['symbol VARCHAR(50) NULL'],
            'gate_passes'               => ['service_id BIGINT UNSIGNED NULL'],
            'holidays'                  => ['branch_id BIGINT UNSIGNED NULL'],
            'inspection_points_library' => [
                'observation_type_id BIGINT UNSIGNED NULL',
                'category VARCHAR(255) NULL',
            ],
            'notification_templates'    => ['subject VARCHAR(255) NULL'],
            'invoices'                  => ['customer_id BIGINT UNSIGNED NULL'],
            'washbays'                  => ['branch_id BIGINT UNSIGNED NULL'],
        ];

        foreach ($changes as $table => $colDefs) {
            if (!Schema::hasTable($table)) continue;
            foreach ($colDefs as $colDef) {
                $colName = explode(' ', $colDef)[0];
                if (!Schema::hasColumn($table, $colName)) continue;
                try {
                    DB::statement("ALTER TABLE `$table` MODIFY $colDef");
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("could not modify $table.$colName: " . $e->getMessage());
                }
            }
        }
    }

    public function down(): void
    {
        // not reversed
    }
};
