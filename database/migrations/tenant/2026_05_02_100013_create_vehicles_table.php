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
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('restrict');
            $table->foreignId('vehicle_brand_id')->constrained('vehicle_brands')->onDelete('restrict');
            $table->foreignId('fuel_type_id')->constrained('fuel_types')->onDelete('restrict');
            $table->string('number_plate')->unique();
            $table->string('engine_number')->nullable();
            $table->string('chassis_number')->nullable();
            $table->string('model_name')->nullable();
            $table->string('model_year')->nullable();
            $table->string('color')->nullable();
            $table->integer('odometer')->default(0);
            $table->decimal('price', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('number_plate');
            $table->index('engine_number');
            $table->index('chassis_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
