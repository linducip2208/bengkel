<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'loyalty_points')) {
                $table->integer('loyalty_points')->default(0)->after('notes');
            }
            if (! Schema::hasColumn('customers', 'membership_tier')) {
                $table->string('membership_tier')->default('bronze')->after('loyalty_points');
            }
            if (! Schema::hasColumn('customers', 'portal_password')) {
                $table->string('portal_password')->nullable()->after('membership_tier');
            }
            if (! Schema::hasColumn('customers', 'portal_last_login')) {
                $table->timestamp('portal_last_login')->nullable()->after('portal_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points', 'membership_tier', 'portal_password', 'portal_last_login']);
        });
    }
};
