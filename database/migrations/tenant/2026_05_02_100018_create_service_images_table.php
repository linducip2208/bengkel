<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('image_path');
            $table->enum('type', ['before', 'after', 'progress']);
            $table->string('caption')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_images');
    }
};
