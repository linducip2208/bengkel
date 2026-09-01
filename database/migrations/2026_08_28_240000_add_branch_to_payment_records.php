<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_records', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('invoice_id')->constrained()->restrictOnDelete();
            $table->index(['branch_id', 'payment_date'], 'payments_branch_date_index');
        });

        // Correlated subquery keeps the backfill portable across MySQL and
        // SQLite (SQLite does not support multi-table UPDATE ... JOIN).
        DB::table('payment_records')
            ->whereNull('branch_id')
            ->whereNotNull('invoice_id')
            ->update([
                'branch_id' => DB::raw(
                    '(select invoices.branch_id from invoices where invoices.id = payment_records.invoice_id)'
                ),
            ]);
    }

    public function down(): void
    {
        Schema::table('payment_records', function (Blueprint $table): void {
            // The FK must be dropped before the index it relies on, otherwise
            // MySQL refuses with error 1553 (index needed by FK constraint).
            $table->dropForeign(['branch_id']);
            $table->dropIndex('payments_branch_date_index');
            $table->dropColumn('branch_id');
        });
    }
};
