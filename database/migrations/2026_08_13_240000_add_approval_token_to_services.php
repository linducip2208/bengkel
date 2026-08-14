<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'approval_token')) {
                $table->string('approval_token', 64)->nullable()->unique()->after('job_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'approval_token')) {
                $table->dropUnique(['approval_token']);
                $table->dropColumn('approval_token');
            }
        });
    }
};
