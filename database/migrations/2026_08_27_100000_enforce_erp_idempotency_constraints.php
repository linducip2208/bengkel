<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('journal_entries')
            ->selectRaw('reference_type, reference_id, entry_type, MIN(id) AS keep_id')
            ->whereNotNull('reference_type')
            ->whereNotNull('reference_id')
            ->groupBy('reference_type', 'reference_id', 'entry_type')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate): void {
                DB::table('journal_entries')
                    ->where('reference_type', $duplicate->reference_type)
                    ->where('reference_id', $duplicate->reference_id)
                    ->where('entry_type', $duplicate->entry_type)
                    ->where('id', '!=', $duplicate->keep_id)
                    ->update(['reference_id' => null]);
            });

        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->unique(
                ['reference_type', 'reference_id', 'entry_type'],
                'journal_entries_source_event_unique'
            );
        });

        DB::table('payment_records')
            ->selectRaw('reference_number, MIN(id) AS keep_id')
            ->whereNotNull('reference_number')
            ->groupBy('reference_number')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $duplicate): void {
                DB::table('payment_records')
                    ->where('reference_number', $duplicate->reference_number)
                    ->where('id', '!=', $duplicate->keep_id)
                    ->update(['reference_number' => null]);
            });

        Schema::table('payment_records', function (Blueprint $table): void {
            $table->unique('reference_number', 'payment_records_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_records', function (Blueprint $table): void {
            $table->dropUnique('payment_records_reference_unique');
        });
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropUnique('journal_entries_source_event_unique');
        });
    }
};
