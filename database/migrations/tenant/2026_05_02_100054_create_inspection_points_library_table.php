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
            $table->foreignId('observation_type_id')->nullable()->constrained('observation_types')->onDelete('set null');
            $table->string('point');
            $table->string('category')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('observation_type_id');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_points_library');
    }
};
