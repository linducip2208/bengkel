<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('checkout_category_id')->constrained('checkout_categories')->onDelete('restrict');
            $table->string('result');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('service_id');
            $table->index('checkout_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_results');
    }
};
