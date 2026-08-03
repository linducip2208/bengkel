<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_passes', function (Blueprint $table) {
            $table->id();
            $table->string('gate_pass_no')->unique();
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('restrict');
            $table->dateTime('entry_date');
            $table->dateTime('exit_date')->nullable();
            $table->text('notes')->nullable();
            $table->integer('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('service_id');
            $table->index('vehicle_id');
            $table->index('customer_id');
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_passes');
    }
};
