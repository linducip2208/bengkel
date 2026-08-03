<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Service Package Templates
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('repair_category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('estimated_hours', 5, 1)->nullable();
            $table->text('description')->nullable();
            $table->text('items')->nullable()->comment('JSON: list sparepart & jasa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Down Payment on invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('dp_amount', 15, 2)->default(0)->after('amount_received');
            $table->string('dp_status')->default('none')->after('dp_amount')->comment('none,dp_paid,full_paid');
        });

        // Barcode column for products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode', 100)->nullable()->unique()->after('code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['dp_amount', 'dp_status']);
        });
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });
        Schema::dropIfExists('service_packages');
    }
};
