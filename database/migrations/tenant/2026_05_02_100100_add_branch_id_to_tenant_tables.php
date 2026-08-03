<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'customers', 'vehicles', 'services', 'invoices',
            'products', 'stock_records', 'purchases', 'sales',
            'incomes', 'expenses', 'gate_passes', 'reminders',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->onDelete('set null');
                    $table->index('branch_id');
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'customers', 'vehicles', 'services', 'invoices',
            'products', 'stock_records', 'purchases', 'sales',
            'incomes', 'expenses', 'gate_passes', 'reminders',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'branch_id')) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                }
            });
        }
    }
};
