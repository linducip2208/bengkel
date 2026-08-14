<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill');
            $table->enum('level', ['basic', 'intermediate', 'expert'])->default('basic');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_skills');
    }
};
