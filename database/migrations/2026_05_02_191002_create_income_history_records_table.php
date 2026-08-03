<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_history_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('label');
            $table->timestamps();
            $table->softDeletes();

            $table->index('income_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_history_records');
    }
};
