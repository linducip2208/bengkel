<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('event', 50);
                $table->nullableMorphs('subject');
                $table->text('description')->nullable();
                $table->json('changes')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
                $table->index(['user_id', 'event', 'created_at']);
            });
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->date('date');
                $table->timestamp('clock_in')->nullable();
                $table->timestamp('clock_out')->nullable();
                $table->string('clock_in_location', 100)->nullable();
                $table->string('clock_out_location', 100)->nullable();
                $table->string('status', 20)->default('present');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'date']);
            });
        }

        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->string('name', 100);
                $table->string('phone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('vehicle_plate', 20);
                $table->string('vehicle_brand', 50)->nullable();
                $table->string('vehicle_model', 50)->nullable();
                $table->timestamp('booking_at');
                $table->text('complaint')->nullable();
                $table->string('status', 20)->default('pending');
                $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
                $table->text('admin_notes')->nullable();
                $table->timestamps();
                $table->index(['status', 'booking_at']);
            });
        }

        if (! Schema::hasTable('equipment')) {
            Schema::create('equipment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->string('name', 150);
                $table->string('code', 30)->nullable()->unique();
                $table->string('category', 50)->nullable();
                $table->date('purchase_date')->nullable();
                $table->decimal('purchase_price', 15, 2)->nullable();
                $table->string('status', 20)->default('active');
                $table->date('next_maintenance_date')->nullable();
                $table->integer('maintenance_interval_days')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('equipment_maintenance_logs')) {
            Schema::create('equipment_maintenance_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade');
                $table->date('maintenance_date');
                $table->string('performed_by', 100)->nullable();
                $table->decimal('cost', 15, 2)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ip_whitelists')) {
            Schema::create('ip_whitelists', function (Blueprint $table) {
                $table->id();
                $table->string('ip', 45);
                $table->string('label', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique('ip');
            });
        }

        if (! Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('api_format', 30)->nullable();
                $table->string('base_url', 255)->nullable();
                $table->string('merchant_id', 100)->nullable();
                $table->text('api_key_encrypted')->nullable();
                $table->text('secret_key_encrypted')->nullable();
                $table->json('extra_headers')->nullable();
                $table->json('extra_config')->nullable();
                $table->string('callback_path', 100)->nullable();
                $table->string('supported_methods', 255)->nullable();
                $table->boolean('is_active')->default(false);
                $table->boolean('is_default')->default(false);
                $table->boolean('sandbox_mode')->default(true);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_links')) {
            Schema::create('payment_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->onDelete('set null');
                $table->string('token', 64)->unique();
                $table->string('external_id', 100)->nullable();
                $table->decimal('amount', 15, 2);
                $table->string('status', 20)->default('pending');
                $table->text('payment_url')->nullable();
                $table->text('qr_string')->nullable();
                $table->json('gateway_response')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'expires_at']);
            });
        }

        if (! Schema::hasTable('petty_cash_transactions')) {
            Schema::create('petty_cash_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->date('date');
                $table->enum('type', ['in', 'out']);
                $table->decimal('amount', 15, 2);
                $table->text('description')->nullable();
                $table->string('reference', 50)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->index(['date', 'type']);
            });
        }

        if (! Schema::hasTable('recalls')) {
            Schema::create('recalls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
                $table->foreignId('vehicle_brand_id')->nullable()->constrained('vehicle_brands')->onDelete('set null');
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->date('issue_date')->nullable();
                $table->string('severity', 20)->default('normal');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->string('reviewer_name', 100)->nullable();
                $table->tinyInteger('rating')->unsigned();
                $table->text('comment')->nullable();
                $table->boolean('is_published')->default(false);
                $table->text('admin_reply')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('salaries')) {
            Schema::create('salaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->integer('period_year');
                $table->tinyInteger('period_month')->unsigned();
                $table->decimal('base_salary', 15, 2)->default(0);
                $table->decimal('commission_total', 15, 2)->default(0);
                $table->decimal('allowance', 15, 2)->default(0);
                $table->decimal('deduction', 15, 2)->default(0);
                $table->decimal('net_salary', 15, 2)->default(0);
                $table->integer('days_present')->default(0);
                $table->integer('days_absent')->default(0);
                $table->string('status', 20)->default('draft');
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'period_year', 'period_month']);
            });
        }

        if (! Schema::hasTable('subcontractors')) {
            Schema::create('subcontractors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
                $table->string('name', 100);
                $table->string('specialty', 50)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('subcontractor_jobs')) {
            Schema::create('subcontractor_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subcontractor_id')->constrained('subcontractors')->onDelete('cascade');
                $table->foreignId('service_id')->nullable()->constrained('services')->onDelete('set null');
                $table->text('description')->nullable();
                $table->decimal('cost', 15, 2)->default(0);
                $table->string('status', 20)->default('assigned');
                $table->date('assigned_date')->nullable();
                $table->date('completed_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('warranty_claims')) {
            Schema::create('warranty_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->onDelete('set null');
                $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $table->date('claim_date');
                $table->text('complaint')->nullable();
                $table->string('status', 20)->default('open');
                $table->text('resolution')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('supplier_prices')) {
            Schema::create('supplier_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->decimal('price', 15, 2)->default(0);
                $table->boolean('is_preferred')->default(false);
                $table->timestamps();
                $table->unique(['supplier_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('fleet_notification_tokens')) {
            Schema::create('fleet_notification_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('token', 255);
                $table->string('platform', 20)->default('web');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['user_id', 'token']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_notification_tokens');
        Schema::dropIfExists('supplier_prices');
        Schema::dropIfExists('warranty_claims');
        Schema::dropIfExists('subcontractor_jobs');
        Schema::dropIfExists('subcontractors');
        Schema::dropIfExists('salaries');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('recalls');
        Schema::dropIfExists('petty_cash_transactions');
        Schema::dropIfExists('payment_links');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('ip_whitelists');
        Schema::dropIfExists('equipment_maintenance_logs');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('activity_logs');
    }
};
