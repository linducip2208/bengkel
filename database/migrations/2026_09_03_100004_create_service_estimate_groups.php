<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estimate WORK PACKAGE GROUPS — one estimate contains one or more groups
 * (each originating from a work package / finding, or manual). Groups carry
 * the customer's per-work-package approval decision.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_estimate_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_estimate_id')->constrained('service_estimates')->cascadeOnDelete();
            $table->foreignId('service_work_package_id')->nullable()->constrained('service_work_packages')->nullOnDelete();
            $table->foreignId('service_finding_id')->nullable()->constrained('service_findings')->nullOnDelete();

            $table->string('title', 255);
            $table->string('severity_snapshot', 20)->nullable();
            $table->unsignedInteger('standard_minutes')->default(0);

            // Server-authoritative money (DECIMAL, never float).
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            // pending|approved|rejected
            $table->string('customer_decision', 20)->default('pending')->index();
            $table->string('decision_reason', 255)->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['service_estimate_id', 'sort_order']);
            $table->index('service_work_package_id');
        });

        Schema::table('service_estimate_items', function (Blueprint $table) {
            $table->foreignId('estimate_group_id')->nullable()->constrained('service_estimate_groups')->nullOnDelete();
            $table->index('estimate_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_estimate_items', function (Blueprint $table) {
            $table->dropForeign(['estimate_group_id']);
            $table->dropIndex(['estimate_group_id']);
            $table->dropColumn('estimate_group_id');
        });

        Schema::dropIfExists('service_estimate_groups');
    }
};
