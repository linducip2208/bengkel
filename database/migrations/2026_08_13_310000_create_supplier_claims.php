<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number');
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('warranty_claim_id')->nullable()->constrained('warranty_claims')->nullOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('claim_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('warranty_claim_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_claims');
    }
};
