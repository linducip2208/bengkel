<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'service_advisor_id')) {
                $table->foreignId('service_advisor_id')->nullable()->after('assign_to')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'service_advisor_id')) {
                $table->dropForeign(['service_advisor_id']);
                $table->dropColumn('service_advisor_id');
            }
        });
    }
};
