<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Parts Usage Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 6px 8px; border: 1px solid #ccc; }
        table th { background: #f5f5f5; }
        .summary { margin-bottom: 15px; }
        .summary span { margin-right: 20px; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Parts Usage Report</h2>
    <div class="subtitle">{{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

    <div class="summary">
        <span>Parts Used: {{ count($report['usages'] ?? []) }}</span>
        <span>Total Cost: @money($report['total_cost'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th>#</th><th>Product</th><th>Category</th><th class="text-center">Qty</th><th class="text-right">Unit Cost</th><th class="text-right">Total Cost</th></tr></thead>
        <tbody>
            @forelse($report['usages'] ?? [] as $u)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $u->product_name }}</td>
                <td>{{ $u->category }}</td>
                <td class="text-center">{{ $u->total_qty ?? 0 }}</td>
                <td class="text-right">@money($u->unit_cost ?? 0)</td>
                <td class="text-right">@money($u->total_cost ?? 0)</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
