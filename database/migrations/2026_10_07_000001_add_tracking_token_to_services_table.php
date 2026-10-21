<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * tracking_token was referenced by TrackingController and the public
     * tracking view but never had a migration (fresh installs 500'd).
     * This migration adds it and backfills existing rows.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('tracking_token', 64)->nullable()->unique()->after('survey_token');
        });

        DB::table('services')->whereNull('tracking_token')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                do {
                    $token = Str::random(40);
                } while (DB::table('services')->where('tracking_token', $token)->exists());

                DB::table('services')->where('id', $row->id)->update(['tracking_token' => $token]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('tracking_token');
        });
    }
};
