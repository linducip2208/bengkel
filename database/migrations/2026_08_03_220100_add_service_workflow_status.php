<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->integer('workflow_status')->default(0)->after('done_status')->comment('0=pending,1=checked_in,2=in_progress,3=qc,4=ready,5=delivered');
            $table->timestamp('checked_in_at')->nullable()->after('completed_at');
            $table->timestamp('qc_passed_at')->nullable()->after('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['workflow_status', 'checked_in_at', 'qc_passed_at']);
        });
    }
};
