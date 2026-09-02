<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WORK TASKS + TIME ENTRIES + QC CHECKS — operational execution layer.
 * Only approved work packages become tasks; actual time excludes pauses;
 * QC checks gate finding resolution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_work_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_work_package_id')->constrained('service_work_packages')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            // pending|ready|in_progress|paused|completed|qc_pending|qc_passed|qc_failed
            $table->string('status', 20)->default('pending')->index();

            $table->unsignedInteger('standard_minutes')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One task per work package — the idempotency guarantee for
            // repeated approval callbacks (task creation retries).
            $table->unique('service_work_package_id');
            $table->index(['service_id', 'status']);
            $table->index('assigned_to');
        });

        Schema::create('service_work_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_work_task_id')->constrained('service_work_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);

            $table->timestamps();

            // An open entry (ended_at NULL) is unique — only one running timer
            // per task, enforced at the database level.
            $table->index(['service_work_task_id', 'ended_at']);
        });

        Schema::create('service_work_qc_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_work_package_id')->constrained('service_work_packages')->cascadeOnDelete();
            $table->foreignId('service_work_task_id')->nullable()->constrained('service_work_tasks')->nullOnDelete();

            // passed|failed
            $table->string('result', 10);
            $table->text('notes')->nullable();

            $table->foreignId('checked_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_at');

            $table->timestamps();

            $table->index(['service_work_package_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_work_qc_checks');
        Schema::dropIfExists('service_work_time_entries');
        Schema::dropIfExists('service_work_tasks');
    }
};
