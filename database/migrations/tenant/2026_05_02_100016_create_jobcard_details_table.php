<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobcard_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('restrict');
            $table->string('jobcard_no')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->integer('odometer_in');
            $table->integer('odometer_out')->nullable();
            $table->dateTime('in_date');
            $table->dateTime('out_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('next_service_km')->nullable();
            $table->integer('done_status')->default(0);
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('service_id');
            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('jobcard_no');
            $table->index('done_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobcard_details');
    }
};
