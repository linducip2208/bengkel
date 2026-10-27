<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom 2FA dipakai AuthController/TwoFactorController dan tercatat
     * di dump produksi, tapi tidak ada migrasi yang membuatnya —
     * install fresh (dan sqlite testing) crash saat enable 2FA.
     * Guard hasColumn agar aman di database yang sudah punya kolomnya.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'two_factor_verified_at')) {
                $table->timestamp('two_factor_verified_at')->nullable()->after('two_factor_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_verified_at', 'two_factor_enabled']);
        });
    }
};
