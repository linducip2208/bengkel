<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number');
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('payment_status')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('grand_total', 15, 2)->nullable();
            $table->decimal('paid_amount', 15, 2)->nullable();
            $table->decimal('amount_received', 15, 2)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_type')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('service_id');
            $table->index('payment_method_id');
            $table->index('created_by');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
