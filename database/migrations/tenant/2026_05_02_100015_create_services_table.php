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
            $table->string('job_no')->unique();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->foreignId('repair_category_id')->nullable()->constrained('repair_categories')->onDelete('restrict');
            $table->string('title');
            $table->dateTime('service_date');
            $table->integer('assign_to')->nullable()->comment('employee user id');
            $table->integer('done_status')->default(0)->comment('0=pending,1=in_progress,2=done');
            $table->decimal('charge', 15, 2)->default(0);
            $table->boolean('mot_status')->default(false);
            $table->boolean('is_quotation')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->integer('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('job_no');
            $table->index('service_date');
            $table->index('done_status');
            $table->index('assign_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
