<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Service Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { padding: 6px 8px; border: 1px solid #ccc; }
        table th { background: #f5f5f5; }
        .summary { margin-bottom: 15px; }
        .summary span { margin-right: 20px; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Service Report</h2>
    <div class="subtitle">{{ request('start_date', '') }} to {{ request('end_date', '') }}</div>

    <div class="summary">
        <span>Total Services: {{ $report['total_services'] ?? 0 }}</span>
        <span>Total Revenue: @money($report['total_revenue'] ?? 0)</span>
        <span>Avg Value: @money($report['avg_value'] ?? 0)</span>
    </div>

    <h4>Service List</h4>
    <table>
        <thead><tr><th>Date</th><th>Job No</th><th>Customer</th><th>Vehicle</th><th class="text-right">Charge</th></tr></thead>
        <tbody>
            @forelse($report['services'] ?? [] as $s)
            <tr>
                <td>{{ $s->service_date ? $s->service_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $s->job_no }}</td>
                <td>{{ $s->customer->name ?? '-' }}</td>
                <td>{{ $s->vehicle->number_plate ?? '-' }}</td>
                <td class="text-right">@money($s->charge ?? 0)</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
