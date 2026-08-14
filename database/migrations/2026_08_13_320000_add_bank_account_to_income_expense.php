<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasColumn('incomes', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('payment_method_id')->constrained('bank_accounts')->nullOnDelete();
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->after('branch_id')->constrained('bank_accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('incomes', function (Blueprint $table) {
            if (Schema::hasColumn('incomes', 'bank_account_id')) {
                $table->dropConstrainedForeignId('bank_account_id');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'bank_account_id')) {
                $table->dropConstrainedForeignId('bank_account_id');
            }
        });
    }
};
