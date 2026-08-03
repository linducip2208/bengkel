<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('restrict');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->date('income_date');
            $table->string('label');
            $table->text('description')->nullable();
            $table->integer('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('income_date');
            $table->index('payment_method_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
