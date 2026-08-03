<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observation_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_type_id')->constrained('observation_types')->onDelete('restrict');
            $table->string('observation_point');
            $table->timestamps();
            $table->softDeletes();

            $table->index('observation_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observation_points');
    }
};
