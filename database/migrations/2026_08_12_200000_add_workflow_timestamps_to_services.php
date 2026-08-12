<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend workflow_status comment and add workflow columns
        Schema::table('services', function (Blueprint $table) {
            // Add missing workflow timestamp columns
            if (!Schema::hasColumn('services', 'inspected_at')) {
                $table->timestamp('inspected_at')->nullable()->after('qc_passed_at')->comment('Timestamp saat inspection selesai');
            }
            if (!Schema::hasColumn('services', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('inspected_at')->comment('Timestamp saat customer approve');
            }
            if (!Schema::hasColumn('services', 'invoiced_at')) {
                $table->timestamp('invoiced_at')->nullable()->after('approved_at')->comment('Timestamp saat invoice dibuat');
            }
            if (!Schema::hasColumn('services', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('invoiced_at')->comment('Timestamp saat invoice lunas');
            }
            if (!Schema::hasColumn('services', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('paid_at')->comment('Timestamp saat kendaraan diambil');
            }
            if (!Schema::hasColumn('services', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('released_at');
            }
            if (!Schema::hasColumn('services', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['inspected_at','approved_at','invoiced_at','paid_at','released_at','cancelled_at','cancel_reason']);
        });
    }
};
