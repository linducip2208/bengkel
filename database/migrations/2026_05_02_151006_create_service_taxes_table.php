<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->decimal('tax_amount', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index('service_id');
            $table->index('tax_rate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_taxes');
    }
};
