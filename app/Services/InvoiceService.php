<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceService extends BaseService
{
    public function create(array $data): Invoice
    {
        $data['invoice_number'] = $this->generateInvoiceNumber();
        $data['created_by'] = auth()->id() ?? 1;

        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['grand_total'] = $data['total_amount'] ?? 0;
        $data['grand_total'] += ($data['tax_amount'] ?? 0);
        $data['grand_total'] -= ($data['discount'] ?? 0);

        $invoice = Invoice::create($data);

        foreach ($items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'total_price' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
            ]);
        }

        return $invoice;
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        $data['grand_total'] = $data['total_amount'] ?? 0;
        $data['grand_total'] += ($data['tax_amount'] ?? 0);
        $data['grand_total'] -= ($data['discount'] ?? 0);

        $invoice->update($data);

        if (!empty($items)) {
            $invoice->items()->delete();
            foreach ($items as $item) {
                $invoice->items()->create([
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

    public function pdf($id) { abort(501); }
}
