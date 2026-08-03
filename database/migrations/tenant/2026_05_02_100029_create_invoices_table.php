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
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('restrict');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('restrict');
            $table->integer('payment_status')->default(0)->comment('0=unpaid,1=half_paid,2=full_paid');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('amount_received', 15, 2)->default(0);
            $table->date('invoice_date');
            $table->enum('invoice_type', ['service', 'sales', 'sales_part']);
            $table->integer('created_by');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('service_id');
            $table->index('payment_method_id');
            $table->index('payment_status');
            $table->index('invoice_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
