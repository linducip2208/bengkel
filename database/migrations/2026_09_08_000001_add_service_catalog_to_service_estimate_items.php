<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_estimate_items', function (Blueprint $table) {
            $table->foreignId('service_catalog_id')
                ->nullable()
                ->after('product_id')
                ->constrained('service_packages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_estimate_items', function (Blueprint $table) {
            $table->dropForeign(['service_catalog_id']);
            $table->dropColumn('service_catalog_id');
        });
    }
};
