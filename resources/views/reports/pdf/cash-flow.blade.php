<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cash Flow Report</title>
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
    <h2>Cash Flow Report</h2>
    <div class="subtitle">{{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

    <div class="summary">
        <span>Income: @money($report['total_income'] ?? 0)</span>
        <span>Expense: @money($report['total_expense'] ?? 0)</span>
        <span>Net: @money($report['net'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th>Date</th><th class="text-right">Income</th><th class="text-right">Expense</th><th class="text-right">Net</th></tr></thead>
        <tbody>
            @forelse($report['daily'] ?? [] as $d)
            <tr>
                <td>{{ \Carbon\Carbon::parse($d['date'])->format('d/m/Y') }}</td>
                <td class="text-right">@money($d['income'])</td>
                <td class="text-right">@money($d['expense'])</td>
                <td class="text-right">@money($d['net'])</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
