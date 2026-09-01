<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconcile remaining columns where inline validators say `nullable`
 * but DB still enforces NOT NULL. Found by scripts/check_nullable_inline.php.
 *
 * Uses the schema builder (portable across MySQL and SQLite) instead of raw
 * `ALTER TABLE ... MODIFY`, which is MySQL-only and fails silently elsewhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->unsignedBigInteger('vehicle_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('services')) {
            Schema::table('services', function (Blueprint $table) {
                $table->unsignedBigInteger('repair_category_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('countries')) {
            Schema::table('countries', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->change();
                $table->string('phone_code', 50)->nullable()->change();
            });
        }

        if (Schema::hasTable('currencies')) {
            Schema::table('currencies', function (Blueprint $table) {
                $table->string('symbol', 50)->nullable()->change();
            });
        }

        if (Schema::hasTable('gate_passes')) {
            Schema::table('gate_passes', function (Blueprint $table) {
                $table->unsignedBigInteger('service_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('holidays')) {
            Schema::table('holidays', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('inspection_points_library')) {
            Schema::table('inspection_points_library', function (Blueprint $table) {
                $table->unsignedBigInteger('observation_type_id')->nullable()->change();
                $table->string('category')->nullable()->change();
            });
        }

        if (Schema::hasTable('notification_templates')) {
            Schema::table('notification_templates', function (Blueprint $table) {
                $table->string('subject')->nullable()->change();
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->change();
            });
        }

        if (Schema::hasTable('washbays')) {
            Schema::table('washbays', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // not reversed
    }
};
