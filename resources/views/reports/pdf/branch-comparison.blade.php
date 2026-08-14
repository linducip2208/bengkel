<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Branch Comparison Report</title>
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
    <h2>Branch Comparison Report</h2>
    <div class="subtitle">{{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

    <div class="summary">
        <span>Branches: {{ count($report['branches'] ?? []) }}</span>
        <span>Total Revenue: @money($report['total_revenue'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th>Branch</th><th class="text-center">Service Count</th><th class="text-right">Service Revenue</th><th class="text-center">POS Count</th><th class="text-right">POS Revenue</th><th class="text-right">Total Revenue</th></tr></thead>
        <tbody>
            @forelse($report['branches'] ?? [] as $b)
            <tr>
                <td>{{ $b['name'] }}</td>
                <td class="text-center">{{ $b['service_count'] }}</td>
                <td class="text-right">@money($b['service_revenue'])</td>
                <td class="text-center">{{ $b['pos_count'] }}</td>
                <td class="text-right">@money($b['pos_revenue'])</td>
                <td class="text-right">@money($b['total_revenue'])</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
