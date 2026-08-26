<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'repeat_of')) {
                $table->foreignId('repeat_of')->nullable()->after('job_no')
                    ->constrained('services')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'repeat_of')) {
                $table->dropForeign(['repeat_of']);
                $table->dropColumn('repeat_of');
            }
        });
    }
};
