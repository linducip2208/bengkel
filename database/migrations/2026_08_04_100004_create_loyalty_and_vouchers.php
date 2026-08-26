<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'loyalty_points')) {
            Schema::table('customers', function (Blueprint $t) {
                $t->integer('loyalty_points')->default(0)->after('address');
                $t->string('membership_tier', 20)->default('bronze')->after('loyalty_points');
            });
        }

        if (! Schema::hasTable('loyalty_transactions')) {
            Schema::create('loyalty_transactions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $t->nullableMorphs('reference');
                $t->integer('points');
                $t->enum('type', ['earn', 'redeem', 'adjust', 'expire']);
                $t->string('description');
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->index(['customer_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $t) {
                $t->id();
                $t->string('code', 30)->unique();
                $t->string('name');
                $t->enum('type', ['percent', 'fixed']);
                $t->decimal('value', 15, 2);
                $t->decimal('min_purchase', 15, 2)->default(0);
                $t->decimal('max_discount', 15, 2)->nullable();
                $t->integer('usage_limit')->nullable();
                $t->integer('used_count')->default(0);
                $t->date('valid_from')->nullable();
                $t->date('valid_until')->nullable();
                $t->boolean('is_active')->default(true);
                $t->text('description')->nullable();
                $t->timestamps();
                $t->softDeletes();
                $t->index(['code', 'is_active']);
            });
        }

        if (! Schema::hasTable('voucher_usages')) {
            Schema::create('voucher_usages', function (Blueprint $t) {
                $t->id();
                $t->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
                $t->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
                $t->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
                $t->decimal('discount_applied', 15, 2);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('loyalty_transactions');
        Schema::table('customers', function (Blueprint $t) {
            if (Schema::hasColumn('customers', 'loyalty_points')) {
                $t->dropColumn(['loyalty_points', 'membership_tier']);
            }
        });
    }
};
