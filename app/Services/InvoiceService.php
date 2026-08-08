<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\StockHistory;

class InvoiceService extends BaseService
{
    public function create(array $data): Invoice
    {
        $data['invoice_number'] = $this->generateInvoiceNumber();
        $data['created_by'] = auth()->id() ?? 1;

        $items = $data['items'] ?? [];
        unset($data['items']);

        // Calculate total from items
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }
        $data['total_amount'] = $totalAmount;
        $data['grand_total'] = $totalAmount + ($data['tax_amount'] ?? 0) - ($data['discount'] ?? 0);

        // Set dp_status based on dp_amount
        $dpAmount = (float) ($data['dp_amount'] ?? 0);
        $data['dp_status'] = $dpAmount > 0 ? 'dp_paid' : 'none';

        $invoice = Invoice::create($data);

        foreach ($items as $item) {
            $invoice->items()->create([
                'product_id' => $item['product_id'] ?? null,
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'total_price' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
            ]);

            // Auto-reduce stock when product_id is linked
            if (!empty($item['product_id'])) {
                $this->reduceStock((int) $item['product_id'], (float) ($item['quantity'] ?? 1), $invoice);
            }
        }

        return $invoice;
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
        }
        $data['total_amount'] = $totalAmount;
        $data['grand_total'] = $totalAmount + ($data['tax_amount'] ?? 0) - ($data['discount'] ?? 0);

        // Update dp_status if dp_amount changed
        $dpAmount = (float) ($data['dp_amount'] ?? $invoice->dp_amount ?? 0);
        $data['dp_status'] = $dpAmount > 0 ? 'dp_paid' : 'none';

        $invoice->update($data);

        if (!empty($items)) {
            $invoice->items()->delete();
            foreach ($items as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total_price' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                ]);
            }
        }

        return $invoice->fresh();
    }

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $last = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();
        $num = $last ? (int)substr($last->invoice_number, -4) + 1 : 1;
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotals(Invoice $invoice): void
    {
        $total = $invoice->items()->sum('total_price');
        $invoice->update([
            'total_amount' => $total,
            'grand_total' => $total + ($invoice->tax_amount ?? 0) - ($invoice->discount ?? 0),
        ]);
    }

    protected function reduceStock(int $productId, float $quantity, Invoice $invoice): void
    {
        $stockRecord = \App\Models\StockRecord::where('product_id', $productId)->first();
        if (!$stockRecord) return;

        $before = $stockRecord->quantity;
        $stockRecord->decrement('quantity', $quantity);

        StockHistory::create([
            'product_id' => $productId,
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'type' => 'out',
            'quantity_change' => -$quantity,
            'previous_stock' => $before,
            'new_stock' => $before - $quantity,
            'reason' => 'Invoice #' . $invoice->invoice_number,
            'user_id' => auth()->id() ?? 1,
        ]);
    }

    public function pdf($id) { abort(501); }
}
