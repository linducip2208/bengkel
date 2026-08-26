<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'survey_token')) {
                $table->string('survey_token', 32)->nullable()->unique()->after('job_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'survey_token')) {
                $table->dropUnique(['survey_token']);
                $table->dropColumn('survey_token');
            }
        });
    }
};
