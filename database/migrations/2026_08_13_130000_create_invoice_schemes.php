<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prefix');
            $table->string('format');
            $table->integer('start_number')->default(1);
            $table->integer('next_number')->default(1);
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_schemes');
    }
};
