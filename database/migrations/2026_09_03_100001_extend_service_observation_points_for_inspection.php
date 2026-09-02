<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade the simple checked/comment checklist into a proper DVI
 * (vehicle inspection) form: per-point condition status plus optional
 * measurement (brake pad mm, tyre mm, battery V, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_observation_points', function (Blueprint $table) {
            // not_checked|ok|attention|repair_required|critical
            $table->string('condition_status', 20)->default('not_checked')->after('checked');
            $table->decimal('measurement_value', 10, 3)->nullable()->after('condition_status');
            $table->string('measurement_unit', 20)->nullable()->after('measurement_value');
            $table->index(['service_id', 'condition_status']);
        });

        // Legacy rows keep their meaning: previously checked == OK.
        DB::table('service_observation_points')
            ->where('checked', 1)
            ->where('condition_status', 'not_checked')
            ->update(['condition_status' => 'ok']);
    }

    public function down(): void
    {
        Schema::table('service_observation_points', function (Blueprint $table) {
            $table->dropIndex(['service_id', 'condition_status']);
            $table->dropColumn(['measurement_unit', 'measurement_value', 'condition_status']);
        });
    }
};
