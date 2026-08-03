<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('estimated_hours', 5, 1)->nullable()->after('charge')->comment('Estimasi lama pengerjaan (jam)');
            $table->dateTime('started_at')->nullable()->after('estimated_hours')->comment('Waktu teknisi mulai kerja');
            $table->dateTime('completed_at')->nullable()->after('started_at')->comment('Waktu selesai');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['estimated_hours', 'started_at', 'completed_at']);
        });
    }
};
