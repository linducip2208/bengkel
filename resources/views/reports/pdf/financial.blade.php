<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 6px 8px; border: 1px solid #ccc; }
        table th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .summary { margin-bottom: 15px; }
        .summary span { margin-right: 30px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Financial Report</h2>
    <div class="subtitle">{{ request('start_date', '') }} to {{ request('end_date', '') }}</div>

    <div class="summary">
        <span>Income: @money($report['total_income'] ?? 0)</span>
        <span>Expense: @money($report['total_expense'] ?? 0)</span>
        <span class="{{ ($report['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
            {{ ($report['profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}:
            @money(abs($report['profit'] ?? 0))
        </span>
    </div>

    <h4>Monthly Breakdown</h4>
    <table>
        <thead><tr><th>Month</th><th class="text-right">Income</th><th class="text-right">Expense</th><th class="text-right">Profit/Loss</th></tr></thead>
        <tbody>
            @forelse($report['monthly_breakdown'] ?? [] as $m)
            <tr>
                <td>{{ $m['month'] }}</td>
                <td class="text-right">@money($m['income'])</td>
                <td class="text-right">@money($m['expense'])</td>
                <td class="text-right {{ $m['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                    @money($m['profit'])
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
