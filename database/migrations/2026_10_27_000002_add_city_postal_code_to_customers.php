<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Form customer sudah lama meminta kota & kode pos, tapi kolomnya
     * belum ada sehingga nilai selalu terbuang. Tambah nullable saja.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('city', 100)->nullable()->after('address');
            $table->string('postal_code', 20)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['city', 'postal_code']);
        });
    }
};
