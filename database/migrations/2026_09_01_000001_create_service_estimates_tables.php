<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_estimates', function (Blueprint $table) {
            $table->id();
            $table->string('estimate_number', 40)->unique();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();

            // Revision chain: same estimate_number, incrementing version.
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('previous_estimate_id')->nullable()->constrained('service_estimates')->nullOnDelete();

            // draft|sent|waiting_approval|approved|rejected|expired|superseded|converted
            $table->string('status', 30)->default('draft')->index();

            $table->date('estimate_date')->nullable();
            $table->date('valid_until')->nullable();

            // Server-authoritative money — never trust client totals.
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->string('discount_type', 20)->default('fixed');
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            // Non-commercial staff notes — editable even on approved documents.
            $table->text('internal_notes')->nullable();

            // Immutable snapshot captured when the document leaves DRAFT.
            $table->json('snapshot')->nullable();

            // Public approval token (unguessable, never exposes row ids).
            $table->string('public_token', 64)->nullable()->unique();

            // Approval evidence.
            $table->string('approval_method', 30)->nullable();
            $table->string('approval_ip', 45)->nullable();
            $table->string('approval_user_agent', 255)->nullable();
            $table->string('approved_hash', 64)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_id', 'status']);
            $table->index(['status', 'valid_until']);
        });

        Schema::create('service_estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_estimate_id')->constrained('service_estimates')->cascadeOnDelete();

            // Nullable: labor / free-text items have no catalog product.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // part|labor|other
            $table->string('item_type', 20)->default('part');

            $table->text('description');

            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);

            $table->decimal('discount', 15, 2)->default(0);
            $table->string('discount_type', 20)->default('fixed');

            $table->decimal('tax_rate', 6, 3)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->decimal('line_total', 15, 2)->default(0);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['service_estimate_id', 'sort_order']);
        });

        // Idempotent estimate → invoice conversion + reconciliation trail.
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('service_estimate_id')->nullable();
            $table->unique('service_estimate_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['service_estimate_id']);
            $table->dropConstrainedForeignId('service_estimate_id');
        });
        Schema::dropIfExists('service_estimate_items');
        Schema::dropIfExists('service_estimates');
    }
};
