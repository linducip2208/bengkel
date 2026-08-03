<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
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
    <h2>Sales Report</h2>
    <div class="subtitle">{{ request('start_date', '') }} to {{ request('end_date', '') }}</div>

    <div class="summary">
        <span>Total Sales: {{ $report['total_sales'] ?? 0 }}</span>
        <span>Total Revenue: @money($report['total_revenue'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th>Date</th><th>Sales No</th><th>Customer</th><th class="text-right">Grand Total</th></tr></thead>
        <tbody>
            @forelse($report['sales'] ?? [] as $s)
            <tr>
                <td>{{ $s->sales_date ? $s->sales_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $s->sales_no }}</td>
                <td>{{ $s->customer->name ?? '-' }}</td>
                <td class="text-right">@money($s->grand_total ?? 0)</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
