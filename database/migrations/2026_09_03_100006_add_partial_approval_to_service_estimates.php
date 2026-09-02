<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partial-approval lifecycle for estimates: a partially approved estimate
 * keeps its own immutable evidence while the commercial decision lives on
 * the groups. approved/rejected amounts are derived and persisted for
 * reporting + invoicing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_estimates', function (Blueprint $table) {
            // pending|partially_approved|approved|rejected (derived from groups)
            $table->string('decision_status', 20)->nullable()->after('status')->index();
            $table->decimal('approved_total', 15, 2)->default(0)->after('decision_status');
            $table->decimal('rejected_total', 15, 2)->default(0)->after('approved_total');
            $table->json('decision_evidence')->nullable()->after('rejected_total');
        });
    }

    public function down(): void
    {
        Schema::table('service_estimates', function (Blueprint $table) {
            $table->dropIndex(['decision_status']);
            $table->dropColumn(['decision_evidence', 'rejected_total', 'approved_total', 'decision_status']);
        });
    }
};
