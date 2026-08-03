<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 6px 8px; border: 1px solid #ccc; }
        table th { background: #f5f5f5; }
        .low-stock td { background-color: #ffe0e0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-bottom: 15px; }
        .summary span { margin-right: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Stock Report</h2>
    <div class="subtitle">Generated: {{ now()->format('d/m/Y H:i') }}</div>

    <div class="summary">
        <span>Total Products: {{ $report['total_products'] ?? 0 }}</span>
        <span>Total Value: @money($report['total_value'] ?? 0)</span>
        <span>Low Stock: {{ ($report['low_stock'] ?? collect())->count() }}</span>
    </div>

    <table>
        <thead><tr><th>Product</th><th>Type</th><th>Stock</th><th class="text-right">Unit Cost</th><th class="text-right">Total Value</th></tr></thead>
        <tbody>
            @forelse($report['products'] ?? [] as $p)
            <tr class="{{ $p->current_stock <= 5 ? 'low-stock' : '' }}">
                <td>{{ $p->name }}</td>
                <td>{{ $p->productType->name ?? '-' }}</td>
                <td>{{ $p->current_stock }}</td>
                <td class="text-right">@money($p->cost_price ?? $p->price ?? 0)</td>
                <td class="text-right">@money($p->total_value ?? 0)</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">No products found</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
