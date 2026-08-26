<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selling_price_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_selling_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('selling_price_group_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 15, 2);
            $table->timestamps();

            $table->unique(['product_id', 'selling_price_group_id']);
        });

        Schema::table('customer_groups', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_groups', 'selling_price_group_id')) {
                $table->foreignId('selling_price_group_id')->nullable()->after('is_active')->constrained('selling_price_groups')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            if (Schema::hasColumn('customer_groups', 'selling_price_group_id')) {
                $table->dropForeign(['selling_price_group_id']);
                $table->dropColumn('selling_price_group_id');
            }
        });

        Schema::dropIfExists('product_selling_prices');
        Schema::dropIfExists('selling_price_groups');
    }
};
