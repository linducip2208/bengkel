<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('service_date')->nullable();
            $table->decimal('charge', 15, 2)->nullable();
            $table->integer('done_status')->default(0);
            $table->foreignId('assign_to')->nullable()->constrained('users')->cascadeOnDelete();
            $table->boolean('mot_status')->default(false);
            $table->boolean('is_quotation')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('job_no')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('repair_category_id');
            $table->index('assign_to');
            $table->index('created_by');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
