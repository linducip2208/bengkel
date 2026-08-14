<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit &amp; Loss Report</title>
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
        .indent { padding-left: 24px !important; }
        .section td { background: #f9fafb; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Profit &amp; Loss Report</h2>
    <div class="subtitle">{{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

    <div class="summary">
        <span>Revenue: @money($report['total_revenue'] ?? 0)</span>
        <span>COGS: @money($report['total_cogs'] ?? 0)</span>
        <span>Expenses: @money($report['total_expenses'] ?? 0)</span>
        <span>Gross Profit: @money($report['gross_profit'] ?? 0)</span>
        <span>Net Profit: @money($report['net_profit'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th colspan="2">Income Statement</th></tr></thead>
        <tbody>
            <tr class="section"><td colspan="2">Revenue</td></tr>
            @forelse($report['revenue_accounts'] ?? [] as $a)
            <tr><td class="indent">{{ $a->name }}</td><td class="text-right">@money($a->balance)</td></tr>
            @empty
            <tr><td class="indent">No revenue data</td><td></td></tr>
            @endforelse
            <tr><td class="indent"><strong>Total Revenue</strong></td><td class="text-right"><strong>@money($report['total_revenue'] ?? 0)</strong></td></tr>

            <tr class="section"><td colspan="2">Cost of Goods Sold</td></tr>
            @forelse($report['cogs_accounts'] ?? [] as $a)
            <tr><td class="indent">{{ $a->name }}</td><td class="text-right">(@money($a->balance))</td></tr>
            @empty
            <tr><td class="indent">No COGS data</td><td></td></tr>
            @endforelse
            <tr><td class="indent"><strong>Gross Profit</strong></td><td class="text-right"><strong>@money($report['gross_profit'] ?? 0)</strong></td></tr>

            <tr class="section"><td colspan="2">Operating Expenses</td></tr>
            @forelse($report['expense_accounts'] ?? [] as $a)
            <tr><td class="indent">{{ $a->name }}</td><td class="text-right">(@money($a->balance))</td></tr>
            @empty
            <tr><td class="indent">No expense data</td><td></td></tr>
            @endforelse

            <tr><td><strong>Net {{ ($report['net_profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}</strong></td><td class="text-right"><strong>@money($report['net_profit'] ?? 0)</strong></td></tr>
        </tbody>
    </table>
</body>
</html>
