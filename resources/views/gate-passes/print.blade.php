<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gate Pass {{ $gatePass->gate_pass_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h2 { margin: 0 0 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table td, table th { padding: 8px; border: 1px solid #ccc; }
        table th { background: #f5f5f5; text-align: left; width: 150px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        .stamp { text-align: right; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
        <h1>GATE PASS</h1>
        <p><strong>{{ $gatePass->gate_pass_no }}</strong></p>
    </div>

    <table>
        <tr><th>Status</th><td>{{ $gatePass->status === 'in' ? 'IN' : 'OUT' }}</td></tr>
        <tr><th>Vehicle</th><td>{{ $gatePass->vehicle->number_plate ?? '-' }} - {{ $gatePass->vehicle->model_name ?? '' }}</td></tr>
        <tr><th>Customer</th><td>{{ $gatePass->vehicle->customer->name ?? $gatePass->customer->name ?? '-' }}</td></tr>
        <tr><th>Phone</th><td>{{ $gatePass->vehicle->customer->phone ?? $gatePass->customer->phone ?? '-' }}</td></tr>
        <tr><th>Entry</th><td>{{ $gatePass->entry_date->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Exit</th><td>{{ $gatePass->exit_date ? $gatePass->exit_date->format('d/m/Y H:i') : '-' }}</td></tr>
        <tr><th>Service</th><td>{{ $gatePass->service->job_no ?? 'No Service' }}</td></tr>
        <tr><th>Driver</th><td>{{ $gatePass->driver_name ?: '-' }}</td></tr>
        <tr><th>Driver Phone</th><td>{{ $gatePass->driver_phone ?: '-' }}</td></tr>
        @if($gatePass->notes)
        <tr><th>Notes</th><td>{{ $gatePass->notes }}</td></tr>
        @endif
    </table>

    <div class="stamp">
        <p>________________________</p>
        <p>Authorized Signature</p>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
