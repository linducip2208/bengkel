<?php

use App\Models\Expense;
use App\Models\PaymentRecord;
use App\Models\Purchase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P0 integrity hardening:
 *  - UNIQUE indexes on every generated document number (after de-duplicating
 *    any legacy duplicates non-destructively).
 *  - invoices.service_id becomes effectively one-to-one (idempotent completion).
 *  - journal_entries.entry_type so one source document can post several
 *    distinct, individually idempotent entries.
 *  - Optional idempotency keys for invoices & payments (double-submit guard).
 *  - Extra indexes for hot filter/join paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. De-duplicate legacy document numbers, then enforce uniqueness.
        // ------------------------------------------------------------------
        $this->dedupeColumn('invoices', 'invoice_number');
        $this->dedupeColumn('sales', 'sales_no');
        $this->dedupeColumn('purchases', 'purchase_no');
        $this->dedupeColumn('gate_passes', 'gate_pass_no');
        $this->dedupeColumn('stock_transfers', 'transfer_number');
        $this->dedupeColumn('supplier_claims', 'claim_number');
        $this->dedupeColumn('insurance_claims', 'claim_number');
        $this->dedupeColumn('services', 'job_no');

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('invoice_number');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->unique('sales_no');
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->unique('purchase_no');
        });
        Schema::table('gate_passes', function (Blueprint $table) {
            $table->unique('gate_pass_no');
        });
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->unique('transfer_number');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->unique('job_no');
        });

        // entry_number is unique per row; legacy PMT/PUR/EXP entries derived
        // from parent IDs are already unique, JRN- manual ones get deduped.
        if ($this->hasDuplicates('journal_entries', 'entry_number')) {
            $this->dedupeColumn('journal_entries', 'entry_number');
        }
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->unique('entry_number');
        });

        if (! Schema::hasColumn('supplier_claims', 'claim_number')) {
            return; // defensive: remaining steps assume base tables exist
        }

        // ------------------------------------------------------------------
        // 2. Journal entry type (payment / purchase / expense / ar_invoice /
        //    cogs / stock_adjustment / ...) — enables per-type idempotency
        //    when one document posts multiple entries.
        // ------------------------------------------------------------------
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('entry_type', 32)->default('')->index()->after('entry_number');
        });

        DB::table('journal_entries')->whereNull('entry_type')->update(['entry_type' => '']);
        DB::table('journal_entries')
            ->where('reference_type', PaymentRecord::class)
            ->where('entry_type', '')
            ->update(['entry_type' => 'payment']);
        DB::table('journal_entries')
            ->where('reference_type', Purchase::class)
            ->where('entry_type', '')
            ->update(['entry_type' => 'purchase']);
        DB::table('journal_entries')
            ->where('reference_type', Expense::class)
            ->where('entry_type', '')
            ->update(['entry_type' => 'expense']);

        // ------------------------------------------------------------------
        // 3. One invoice per completed service (nullable unique => many NULLs).
        // ------------------------------------------------------------------
        $this->keepEarliest('invoices', 'service_id');
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('service_id');
        });

        // ------------------------------------------------------------------
        // 4. Idempotency keys (client-supplied) for invoices and payments.
        // ------------------------------------------------------------------
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('public_token');
        });
        Schema::table('payment_records', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('notes');
        });

        if ($this->hasDuplicates('invoices', 'idempotency_key')) {
            $this->dedupeNullable('invoices', 'idempotency_key');
        }
        if ($this->hasDuplicates('payment_records', 'idempotency_key')) {
            $this->dedupeNullable('payment_records', 'idempotency_key');
        }
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('idempotency_key');
        });
        Schema::table('payment_records', function (Blueprint $table) {
            $table->unique('idempotency_key');
        });

        // ------------------------------------------------------------------
        // 5. Performance indexes for hot paths.
        // ------------------------------------------------------------------
        Schema::table('services', function (Blueprint $table) {
            if (! $this->indexExists('services', 'services_workflow_status_index')) {
                $table->index('workflow_status');
            }
            if (! $this->indexExists('services', 'services_service_date_index')) {
                $table->index('service_date');
            }
        });
        Schema::table('stock_histories', function (Blueprint $table) {
            if (! $this->indexExists('stock_histories', 'stock_histories_product_created_idx')) {
                $table->index(['product_id', 'created_at'], 'stock_histories_product_created_idx');
            }
        });
        Schema::table('payment_records', function (Blueprint $table) {
            if (! $this->indexExists('payment_records', 'payment_records_payment_date_index')) {
                $table->index('payment_date');
            }
        });
        Schema::table('part_reservations', function (Blueprint $table) {
            if (! $this->indexExists('part_reservations', 'part_reservations_product_status_idx')) {
                $table->index(['product_id', 'status'], 'part_reservations_product_status_idx');
            }
        });
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            if (! $this->indexExists('journal_entry_lines', 'journal_entry_lines_entry_account_idx')) {
                $table->index(['journal_entry_id', 'chart_of_account_id'], 'journal_entry_lines_entry_account_idx');
            }
        });

        // A voucher can only ever be redeemed once per invoice.
        if (Schema::hasTable('voucher_usages') && ! $this->indexExists('voucher_usages', 'voucher_usages_voucher_invoice_unique')) {
            Schema::table('voucher_usages', function (Blueprint $table) {
                $table->unique(['voucher_id', 'invoice_id'], 'voucher_usages_voucher_invoice_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('voucher_usages') && $this->indexExists('voucher_usages', 'voucher_usages_voucher_invoice_unique')) {
            Schema::table('voucher_usages', function (Blueprint $table) {
                $table->dropUnique('voucher_usages_voucher_invoice_unique');
            });
        }
        foreach ([
            ['journal_entry_lines', 'journal_entry_lines_entry_account_idx'],
            ['part_reservations', 'part_reservations_product_status_idx'],
            ['payment_records', 'payment_records_payment_date_index'],
            ['stock_histories', 'stock_histories_product_created_idx'],
        ] as [$table, $index]) {
            if (Schema::hasTable($table) && $this->indexExists($table, $index)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($index));
            }
        }
        if (Schema::hasColumn('payment_records', 'idempotency_key')) {
            Schema::table('payment_records', function (Blueprint $table) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            });
        }
        if (Schema::hasColumn('invoices', 'idempotency_key')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropUnique(['service_id']);
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            });
        } elseif (Schema::hasColumn('invoices', 'service_id') && $this->indexExists('invoices', 'invoices_service_id_unique')) {
            Schema::table('invoices', fn (Blueprint $table) => $table->dropUnique(['service_id']));
        }
        if (Schema::hasColumn('journal_entries', 'entry_type')) {
            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropIndex(['entry_type']);
                $table->dropColumn('entry_type');
            });
        }
        foreach ([
            ['journal_entries', 'entry_number'],
            ['services', 'job_no'],
            ['stock_transfers', 'transfer_number'],
            ['gate_passes', 'gate_pass_no'],
            ['purchases', 'purchase_no'],
            ['sales', 'sales_no'],
            ['invoices', 'invoice_number'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $t) use ($column) {
                    try {
                        $t->dropUnique([$column]);
                    } catch (Throwable) {
                    }
                });
            }
        }
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function hasDuplicates(string $table, string $column): bool
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return false;
        }

        return $this->duplicateGroups($table, $column)->isNotEmpty();
    }

    /** @return Collection<int, object{value: mixed, ids: array<int,int>}> */
    private function duplicateGroups(string $table, string $column): Collection
    {
        return DB::table($table)
            ->selectRaw("{$column} AS value, MIN(id) AS keep_id")
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(function ($row) use ($table, $column) {
                return (object) [
                    'value' => $row->value,
                    'keep_id' => $row->keep_id,
                    'ids' => DB::table($table)
                        ->where($column, $row->value)
                        ->where('id', '!=', $row->keep_id)
                        ->orderBy('id')
                        ->pluck('id')
                        ->all(),
                ];
            });
    }

    /**
     * Rename duplicate values by appending a "-D{n}" suffix (keeps history,
     * never deletes data), then the unique index can be applied safely.
     */
    private function dedupeColumn(string $table, string $column): void
    {
        foreach ($this->duplicateGroups($table, $column) as $group) {
            foreach (array_values($group->ids) as $i => $id) {
                DB::table($table)
                    ->where('id', $id)
                    ->update([$column => $group->value.'-D'.($i + 2)]);
            }
        }
    }

    private function dedupeNullable(string $table, string $column): void
    {
        foreach ($this->duplicateGroups($table, $column) as $group) {
            DB::table($table)->whereIn('id', $group->ids)->update([$column => null]);
        }
    }

    /** Null-out duplicates of a nullable FK, keeping the earliest row. */
    private function keepEarliest(string $table, string $column): void
    {
        foreach ($this->duplicateGroups($table, $column) as $group) {
            DB::table($table)->whereIn('id', $group->ids)->update([$column => null]);
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        return collect($indexes)->contains(fn ($i) => $i['name'] === $indexName);
    }
};
