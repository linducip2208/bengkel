<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
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
        .indent { padding-left: 24px !important; }
        .section td { background: #f9fafb; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Balance Sheet</h2>
    <div class="subtitle">As of: {{ $filters['end_date'] ?? now()->toDateString() }}</div>

    <div class="summary">
        <span>Total Assets: @money($report['total_assets'] ?? 0)</span>
        <span>Total Liabilities: @money($report['total_liabilities'] ?? 0)</span>
        <span>Total Equity: @money($report['total_equity'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th colspan="2">Statement of Financial Position</th></tr></thead>
        <tbody>
            <tr class="section"><td colspan="2">ASSETS</td></tr>
            @forelse($report['asset_accounts'] ?? [] as $a)
            <tr><td class="indent">{{ $a->code }} — {{ $a->name }}</td><td class="text-right">@money($a->balance)</td></tr>
            @empty
            <tr><td class="indent">No asset accounts</td><td></td></tr>
            @endforelse
            <tr><td class="indent"><strong>Total Assets</strong></td><td class="text-right"><strong>@money($report['total_assets'] ?? 0)</strong></td></tr>

            <tr class="section"><td colspan="2">LIABILITIES</td></tr>
            @forelse($report['liability_accounts'] ?? [] as $a)
            <tr><td class="indent">{{ $a->code }} — {{ $a->name }}</td><td class="text-right">@money($a->balance)</td></tr>
            @empty
            <tr><td class="indent">No liability accounts</td><td></td></tr>
            @endforelse
            <tr><td class="indent"><strong>Total Liabilities</strong></td><td class="text-right"><strong>@money($report['total_liabilities'] ?? 0)</strong></td></tr>

            <tr class="section"><td colspan="2">EQUITY</td></tr>
            @forelse($report['equity_accounts'] ?? [] as $a)
            <tr><td class="indent">{{ $a->code }} — {{ $a->name }}</td><td class="text-right">@money($a->balance)</td></tr>
            @empty
            <tr><td class="indent">No equity accounts</td><td></td></tr>
            @endforelse
            <tr><td class="indent"><strong>Total Equity</strong></td><td class="text-right"><strong>@money($report['total_equity'] ?? 0)</strong></td></tr>
        </tbody>
    </table>
</body>
</html>
