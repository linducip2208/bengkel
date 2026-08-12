<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'repair_category_id')) {
                $table->foreignId('repair_category_id')->nullable()->after('branch_id')->constrained('repair_categories')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'repair_category_id')) {
                $table->dropForeign(['repair_category_id']);
                $table->dropColumn('repair_category_id');
            }
        });
    }
};
