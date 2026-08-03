<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            if (!Schema::hasColumn('gate_passes', 'status')) {
                $table->enum('status', ['in', 'out'])->default('in')->after('exit_date');
            }
            if (!Schema::hasColumn('gate_passes', 'driver_name')) {
                $table->string('driver_name')->nullable()->after('exit_date');
            }
            if (!Schema::hasColumn('gate_passes', 'driver_phone')) {
                $table->string('driver_phone')->nullable()->after('driver_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->dropColumn(['status', 'driver_name', 'driver_phone']);
        });
    }
};
