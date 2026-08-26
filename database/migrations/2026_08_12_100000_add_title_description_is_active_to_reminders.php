<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            if (! Schema::hasColumn('reminders', 'title')) {
                $table->string('title')->nullable()->after('vehicle_id');
            }
            if (! Schema::hasColumn('reminders', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('reminders', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'is_active']);
        });
    }
};
