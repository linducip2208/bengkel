<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_group_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tax_group_id', 'tax_rate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_group_rates');
        Schema::dropIfExists('tax_groups');
    }
};
