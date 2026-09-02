<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WORK PACKAGE — the commercial/operational bridge between a technical
 * finding and the customer estimate. Labor + parts + standard time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_work_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_finding_id')->nullable()->constrained('service_findings')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 255);
            $table->text('description')->nullable();

            // Severity at creation time — historical evidence, never mutated.
            $table->string('severity_snapshot', 20)->nullable();

            $table->unsignedInteger('standard_minutes')->default(0);

            // draft|proposed|approved|rejected|in_progress|completed|qc_passed|qc_failed|cancelled
            $table->string('status', 20)->default('draft')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_id', 'status']);
            $table->index('service_finding_id');
        });

        Schema::create('service_work_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_work_package_id')->constrained('service_work_packages')->cascadeOnDelete();

            // labor|part|other
            $table->string('item_type', 20)->default('labor');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('description', 255);
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->unsignedInteger('standard_minutes')->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Short explicit names — MySQL identifier limit is 64 chars and
            // the default derived name would exceed it.
            $table->index(['service_work_package_id', 'sort_order'], 'swp_items_pkg_sort_index');
            $table->index('product_id', 'swp_items_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_work_package_items');
        Schema::dropIfExists('service_work_packages');
    }
};
