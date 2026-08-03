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
            $table->string('gate_pass_no');
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->dateTime('entry_date')->nullable();
            $table->dateTime('exit_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('gate_pass_no');
            $table->index('vehicle_id');
            $table->index('customer_id');
            $table->index('service_id');
            $table->index('created_by');
            $table->index('branch_id');
            $table->index('status');
            $table->index('entry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_passes');
    }
};
