<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_no')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('product_type_id')->constrained('product_types')->onDelete('restrict');
            $table->foreignId('unit_id')->constrained('product_units')->onDelete('restrict');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('restrict');
            $table->decimal('price', 15, 2);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->string('warranty')->nullable()->comment('warranty period');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('product_type_id');
            $table->index('unit_id');
            $table->index('supplier_id');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
