<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('washbays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('current_service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
            $table->index('current_service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('washbays');
    }
};
