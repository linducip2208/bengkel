<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('restrict');
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict');
            $table->decimal('amount', 15, 2);
            $table->dateTime('payment_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->integer('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_id');
            $table->index('payment_method_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
