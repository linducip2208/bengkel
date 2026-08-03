<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_observation_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('observation_point_id')->constrained()->cascadeOnDelete();
            $table->boolean('checked')->default(false);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('service_id');
            $table->index('observation_point_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_observation_points');
    }
};
