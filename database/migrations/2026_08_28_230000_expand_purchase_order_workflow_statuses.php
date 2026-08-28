<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = [
        'draft', 'submitted', 'approved', 'sent', 'partially_received', 'received', 'closed', 'cancelled',
    ];

    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('status', self::STATUSES)->default('draft')->change();
        });
        DB::table('purchase_orders')->where('status', 'sent')->update(['status' => 'submitted']);
    }

    public function down(): void
    {
        DB::table('purchase_orders')->whereIn('status', ['submitted', 'approved', 'partially_received'])->update(['status' => 'sent']);
        DB::table('purchase_orders')->where('status', 'closed')->update(['status' => 'received']);
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('status', ['draft', 'sent', 'received', 'cancelled'])->default('draft')->change();
        });
    }
};
