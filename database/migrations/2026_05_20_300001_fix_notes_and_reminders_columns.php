<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            if (Schema::hasColumn('notes', 'author') && !Schema::hasColumn('notes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('content');
            } elseif (!Schema::hasColumn('notes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('content');
            }
        });

        if (Schema::hasColumn('notes', 'author')) {
            DB::statement('UPDATE notes SET created_by = NULL WHERE created_by IS NULL');
            Schema::table('notes', function (Blueprint $table) {
                $table->dropColumn('author');
            });
        }

        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('reminders', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('reminders', 'message')) {
                $table->text('message')->nullable()->after('reminder_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            if (!Schema::hasColumn('notes', 'author')) {
                $table->string('author')->nullable();
            }
            if (Schema::hasColumn('notes', 'created_by')) {
                $table->dropColumn('created_by');
            }
        });

        Schema::table('reminders', function (Blueprint $table) {
            if (Schema::hasColumn('reminders', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('reminders', 'message')) {
                $table->dropColumn('message');
            }
        });
    }
};
