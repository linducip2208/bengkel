<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central transaction-safe sequence storage for every document number
 * (products, invoices, POS receipts, sales, purchases, POs, requisitions,
 * services/job cards, transfers, gate passes, journals, claims, returns).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();
        });

        // Seed sequences from current maxima so new numbers never collide
        // with legacy documents.
        $seeds = [
            'products' => $this->maxLike('products', 'product_no', 'PRD-'),
            'invoices' => $this->maxLike('invoices', 'invoice_number', 'INV-'),
            'pos_invoices' => $this->maxLike('invoices', 'invoice_number', 'POS-'),
            'sales' => $this->maxLike('sales', 'sales_no', 'SLS-'),
            'purchases' => $this->maxLike('purchases', 'purchase_no'),
            'purchase_orders' => $this->maxLike('purchase_orders', 'po_number', 'PO-'),
            'requisitions' => $this->maxLike('purchase_requisitions', 'requisition_number', 'REQ-'),
            'services' => $this->maxLike('services', 'job_no', 'BP-'),
            'stock_transfers' => $this->maxLike('stock_transfers', 'transfer_number', 'TRF-'),
            'gate_passes' => $this->maxLike('gate_passes', 'gate_pass_no', 'GP-'),
            'journals' => $this->maxLike('journal_entries', 'entry_number', 'JRN-'),
            'supplier_claims' => $this->maxLike('supplier_claims', 'claim_number', 'SCL-'),
            'insurance_claims' => $this->maxLike('insurance_claims', 'claim_number', 'ASR-'),
            'sell_returns' => $this->maxLike('sell_returns', 'return_number', 'RET-'),
        ];

        foreach ($seeds as $key => $value) {
            if ($value > 0) {
                DB::table('document_sequences')->insertOrIgnore([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }

    /**
     * Highest trailing number currently used for a prefix, so the sequence
     * can continue from it instead of colliding with existing rows.
     */
    private function maxLike(string $table, string $column, ?string $prefix = null): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $query = DB::table($table);
        if ($prefix !== null) {
            $query->where($column, 'like', $prefix.'%');
        }

        $values = $query
            ->orderByDesc($column)
            ->limit(500)
            ->pluck($column);

        $max = 0;
        foreach ($values as $value) {
            if (preg_match('/(\d+)$/', (string) $value, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max;
    }
};
