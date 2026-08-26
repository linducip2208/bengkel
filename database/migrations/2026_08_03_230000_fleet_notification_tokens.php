<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_groups')) {
            Schema::create('customer_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'customer_group_id')) {
                $table->foreignId('customer_group_id')->nullable()->after('id')->constrained('customer_groups')->nullOnDelete();
            }
            if (! Schema::hasColumn('customers', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('phone');
            }
        });

        if (! Schema::hasTable('notification_queue')) {
            Schema::create('notification_queue', function (Blueprint $table) {
                $table->id();
                $table->string('channel');
                $table->string('recipient');
                $table->text('message');
                $table->json('metadata')->nullable();
                $table->string('status')->default('pending');
                $table->text('error')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'channel']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'customer_group_id')) {
                $table->dropForeign(['customer_group_id']);
                $table->dropColumn('customer_group_id');
            }
            if (Schema::hasColumn('customers', 'birth_date')) {
                $table->dropColumn('birth_date');
            }
        });
    }
};
