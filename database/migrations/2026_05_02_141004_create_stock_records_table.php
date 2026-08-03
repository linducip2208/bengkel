<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->integer('quantity')->default(0);
            $table->integer('minimum_stock')->default(0);
            $table->string('rack_location')->nullable();
            $table->foreignId('branch_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_id');
            $table->index('supplier_id');
            $table->index('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_records');
    }
};
