<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Technical FINDING between checklist and commercial estimate.
 * A finding is a technical observation — never a price.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_observation_point_id')->nullable()->constrained('service_observation_points')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            $table->string('finding_number', 40)->unique();

            // attention|repair_required|critical
            $table->string('severity', 20);

            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('technician_note')->nullable();
            $table->text('recommendation')->nullable();

            $table->decimal('measurement_value', 10, 3)->nullable();
            $table->string('measurement_unit', 20)->nullable();

            // open|work_proposed|approved_for_work|in_progress|resolved|deferred|cancelled
            $table->string('status', 20)->default('open')->index();

            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_id', 'status']);
            $table->index('service_observation_point_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_findings');
    }
};
