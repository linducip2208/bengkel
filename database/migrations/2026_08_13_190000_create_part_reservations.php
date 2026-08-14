<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['reserved', 'released', 'consumed'])->default('reserved');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('service_id');
            $table->index('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_reservations');
    }
};
