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

        DB::table('payment_records')->join('invoices', 'invoices.id', '=', 'payment_records.invoice_id')
            ->whereNull('payment_records.branch_id')
            ->update(['payment_records.branch_id' => DB::raw('invoices.branch_id')]);
    }

    public function down(): void
    {
        Schema::table('payment_records', function (Blueprint $table): void {
            $table->dropIndex('payments_branch_date_index');
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
