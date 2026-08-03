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
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('jobcard_no');
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->integer('odometer_in')->nullable();
            $table->integer('odometer_out')->nullable();
            $table->datetime('in_date')->nullable();
            $table->datetime('out_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('next_service_km')->nullable();
            $table->integer('done_status')->nullable();
            $table->boolean('reminder_sent')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('service_id');
            $table->index('customer_id');
            $table->index('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobcard_details');
    }
};
