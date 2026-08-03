<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sales_no')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('restrict');
            $table->date('sales_date');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->integer('salesperson_id')->nullable();
            $table->text('notes')->nullable();
            $table->integer('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('sales_date');
            $table->index('salesperson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
