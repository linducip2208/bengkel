<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index kolom yang dipakai filter/sort laporan (additive, aman
     * dijalankan ulang di produksi — hanya mempercepat query baca).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('payment_status');
            $table->index('invoice_date');
            $table->index('invoice_type');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index('done_status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['invoice_date']);
            $table->dropIndex(['invoice_type']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['done_status']);
        });
    }
};
