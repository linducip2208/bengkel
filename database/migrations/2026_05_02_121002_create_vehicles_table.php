<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fuel_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('number_plate');
            $table->string('chassis_number')->nullable();
            $table->string('engine_number')->nullable();
            $table->string('model_name')->nullable();
            $table->integer('model_year')->nullable();
            $table->string('color')->nullable();
            $table->integer('odometer')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('vehicle_type_id');
            $table->index('vehicle_brand_id');
            $table->index('fuel_type_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
