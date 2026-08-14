<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>General Ledger</title>
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
    <h2>General Ledger</h2>
    <div class="subtitle">{{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

    <div class="summary">
        <span>Total Debit: @money($report['total_debit'] ?? 0)</span>
        <span>Total Credit: @money($report['total_credit'] ?? 0)</span>
        <span>Balance: @money(($report['total_debit'] ?? 0) - ($report['total_credit'] ?? 0))</span>
    </div>

    <table>
        <thead><tr><th>Date</th><th>Entry #</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
        <tbody>
            @forelse($report['entries'] ?? [] as $e)
            <tr>
                <td>{{ \Carbon\Carbon::parse($e->entry_date)->format('d/m/Y') }}</td>
                <td>{{ $e->entry_number }}</td>
                <td>{{ $e->description }}</td>
                <td class="text-right">@money($e->total_debit ?? 0)</td>
                <td class="text-right">@money($e->total_credit ?? 0)</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
