<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Lifetime Value Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 5px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 6px 8px; border: 1px solid #ccc; }
        table th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <h2>Customer Lifetime Value Report</h2>
    <div class="subtitle">Top 20 Customers</div>

    <table>
        <thead><tr><th>#</th><th>Customer</th><th class="text-center">Visits</th><th class="text-right">Lifetime Value</th><th class="text-right">Avg Per Visit</th><th>Last Visit</th></tr></thead>
        <tbody>
            @forelse($report['customers'] ?? [] as $c)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $c->name ?? '-' }}</td>
                <td class="text-center">{{ $c->services_count ?? 0 }}</td>
                <td class="text-right">@money($c->lifetime_value ?? 0)</td>
                <td class="text-right">@money($c->avg_per_visit ?? 0)</td>
                <td>{{ $c->last_service ? $c->last_service->format('d/m/Y') : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center">No data</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
