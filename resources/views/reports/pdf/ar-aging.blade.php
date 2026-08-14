<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AR Aging Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 6px 8px; border: 1px solid #ccc; }
        table th { background: #f5f5f5; }
        .summary { margin-bottom: 15px; }
        .summary span { margin-right: 15px; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>AR Aging Report</h2>
    <div class="subtitle">Generated: {{ now()->format('d/m/Y') }}</div>

    <div class="summary">
        <span>Outstanding: {{ count($report['invoices'] ?? []) }}</span>
        <span>Current: @money($report['aging']['current']['total'] ?? 0)</span>
        <span>1-30: @money($report['aging']['1-30']['total'] ?? 0)</span>
        <span>31-60: @money($report['aging']['31-60']['total'] ?? 0)</span>
        <span>61-90: @money($report['aging']['61-90']['total'] ?? 0)</span>
        <span>90+: @money($report['aging']['90+']['total'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th>Invoice</th><th>Customer</th><th>Invoice Date</th><th>Due Date</th><th class="text-center">Days Overdue</th><th class="text-right">Remaining</th><th>Age Group</th></tr></thead>
        <tbody>
            @forelse($report['invoices'] ?? [] as $inv)
            <tr>
                <td>{{ $inv->invoice_number }}</td>
                <td>{{ $inv->customer?->name ?? '-' }}</td>
                <td>{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}</td>
                <td class="text-center">{{ $inv->days_overdue ?? 0 }}</td>
                <td class="text-right">@money($inv->remaining ?? 0)</td>
                <td>{{ $inv->age_group ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center">All invoices paid</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
