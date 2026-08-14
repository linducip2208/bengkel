<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Technician Performance Report</title>
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
    <h2>Technician Performance Report</h2>
    <div class="subtitle">{{ $filters['start_date'] ?? '' }} to {{ $filters['end_date'] ?? '' }}</div>

    <div class="summary">
        <span>Total Jobs: {{ $report['total_jobs'] ?? 0 }}</span>
        <span>Total Revenue: @money($report['total_revenue'] ?? 0)</span>
    </div>

    <table>
        <thead><tr><th>Technician</th><th class="text-center">Jobs</th><th class="text-right">Revenue</th><th class="text-right">Avg Duration (hrs)</th></tr></thead>
        <tbody>
            @forelse($report['technicians'] ?? [] as $t)
            <tr>
                <td>{{ $t->technician_name ?? '-' }}</td>
                <td class="text-center">{{ $t->job_count ?? 0 }}</td>
                <td class="text-right">@money($t->total_revenue ?? 0)</td>
                <td class="text-right">{{ $t->avg_duration ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
