<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity_change');
            $table->integer('previous_stock');
            $table->integer('new_stock');
            $table->string('type');
            $table->nullableMorphs('reference');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->index('user_id');
            $table->timestamps();

            $table->index('product_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
