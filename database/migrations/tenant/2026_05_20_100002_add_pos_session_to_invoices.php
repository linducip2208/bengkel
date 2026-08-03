<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'pos_session_id')) {
                $table->foreignId('pos_session_id')->nullable()->after('sale_id')
                    ->constrained('pos_sessions')->onDelete('set null');
                $table->index('pos_session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'pos_session_id')) {
                $table->dropForeign(['pos_session_id']);
                $table->dropColumn('pos_session_id');
            }
        });
    }
};
