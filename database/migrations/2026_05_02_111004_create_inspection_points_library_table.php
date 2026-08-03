<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_points_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('observation_type_id')->constrained()->cascadeOnDelete();
            $table->string('point');
            $table->string('category');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('observation_type_id');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_points_library');
    }
};
