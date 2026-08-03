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
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->enum('status', ['available', 'in_use', 'maintenance'])->default('available');
            $table->foreignId('current_service_id')->nullable()->constrained('services')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('branch_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('washbays');
    }
};
