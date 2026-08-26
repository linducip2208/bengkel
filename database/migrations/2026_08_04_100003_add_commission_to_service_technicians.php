<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_technicians', function (Blueprint $table) {
            if (! Schema::hasColumn('service_technicians', 'role')) {
                $table->string('role', 30)->default('lead')->after('user_id');
            }
            if (! Schema::hasColumn('service_technicians', 'commission_pct')) {
                $table->decimal('commission_pct', 5, 2)->default(10.00)->after('role');
            }
            if (! Schema::hasColumn('service_technicians', 'commission_amt')) {
                $table->decimal('commission_amt', 15, 2)->nullable()->after('commission_pct');
            }
            if (! Schema::hasColumn('service_technicians', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('commission_amt');
            }
            if (! Schema::hasColumn('service_technicians', 'paid_by')) {
                $table->unsignedBigInteger('paid_by')->nullable()->after('paid_at');
                $table->index('paid_by');
            }
            if (! Schema::hasColumn('service_technicians', 'notes')) {
                $table->text('notes')->nullable()->after('paid_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_technicians', function (Blueprint $table) {
            foreach (['role', 'commission_pct', 'commission_amt', 'paid_at', 'paid_by', 'notes'] as $col) {
                if (Schema::hasColumn('service_technicians', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
