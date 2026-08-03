<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types')->onDelete('restrict');
            $table->string('vehicle_brand');
            $table->timestamps();
            $table->softDeletes();

            $table->index('vehicle_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_brands');
    }
};
