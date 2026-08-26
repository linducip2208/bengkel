<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_technicians', function (Blueprint $table) {
            if (! Schema::hasColumn('service_technicians', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('service_technicians', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_technicians', function (Blueprint $table) {
            foreach (['started_at', 'finished_at'] as $col) {
                if (Schema::hasColumn('service_technicians', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
