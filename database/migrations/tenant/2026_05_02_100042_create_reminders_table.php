<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('restrict');
            $table->enum('reminder_type', ['service', 'insurance', 'stnk', 'kir']);
            $table->date('reminder_date');
            $table->boolean('sent')->default(false);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_id');
            $table->index('vehicle_id');
            $table->index('reminder_type');
            $table->index('reminder_date');
            $table->index('sent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
