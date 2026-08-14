<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('held_pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->json('items');
            $table->decimal('discount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('held_pos_transactions');
    }
};
