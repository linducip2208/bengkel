<?php

namespace App\Services;

use App\Models\Sale;

class SaleService extends BaseService
{
    public function create(array $data): Sale
    {
        $data['sales_no'] = $this->generateSalesNo();
        $data['created_by'] = auth()->id() ?? 1;
        $data['grand_total'] = ($data['total_amount'] ?? 0) + ($data['tax_amount'] ?? 0);
        return Sale::create($data);
    }

    public function update(Sale $sale, array $data): Sale
    {
        $data['grand_total'] = ($data['total_amount'] ?? $sale->total_amount) + ($data['tax_amount'] ?? $sale->tax_amount);
        $sale->update($data);
        return $sale;
    }

    public function generateSalesNo(): string
    {
        $prefix = 'SLS-' . date('Ym') . '-';
        $last = Sale::where('sales_no', 'like', $prefix . '%')->orderBy('sales_no', 'desc')->first();
        $num = $last ? (int)substr($last->sales_no, -4) + 1 : 1;
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
