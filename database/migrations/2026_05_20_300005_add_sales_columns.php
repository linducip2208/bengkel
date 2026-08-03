<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Sales view uses fields `price`, `down_payment`, `status`, `description` —
 * these were missing from the DB schema, so create/edit/show pages were broken.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales')) return;

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'price')) {
                $table->decimal('price', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('sales', 'down_payment')) {
                $table->decimal('down_payment', 15, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('sales', 'status')) {
                $table->string('status', 30)->default('pending')->after('down_payment');
            }
            if (!Schema::hasColumn('sales', 'description')) {
                $table->text('description')->nullable()->after('notes');
            }
        });

        DB::statement("UPDATE sales SET price = grand_total WHERE price = 0 AND grand_total > 0");
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            foreach (['price', 'down_payment', 'status', 'description'] as $c) {
                if (Schema::hasColumn('sales', $c)) $table->dropColumn($c);
            }
        });
    }
};
