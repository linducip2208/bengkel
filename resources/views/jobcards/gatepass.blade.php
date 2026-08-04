<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Gate Pass - {{ $service->job_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 10px; }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 8px; }
        .header h3 { margin: 0; font-size: 14px; }
        .header small { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 3px 4px; font-size: 11px; }
        table td.label { font-weight: bold; width: 80px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .footer { text-align: center; margin-top: 15px; font-size: 10px; }
        .barcode { text-align: center; margin: 10px 0; font-size: 9px; letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h3>GATE PASS</h3>
        <small>Aplikasi Bengkel Terbaik</small>
    </div>

    <div class="barcode">{{ $service->job_no }}</div>

    <table>
        <tr><td class="label">Job No</td><td>: {{ $service->job_no }}</td></tr>
        <tr><td class="label">Tanggal</td><td>: {{ now()->format('d M Y H:i') }}</td></tr>
        <tr><td class="label">Pelanggan</td><td>: {{ $service->customer->name ?? '-' }}</td></tr>
        <tr><td class="label">No. HP</td><td>: {{ $service->customer->phone ?? '-' }}</td></tr>
        <tr><td class="label">Kendaraan</td><td>: {{ $service->vehicle->number_plate ?? '-' }}</td></tr>
        <tr><td class="label">Keluhan</td><td>: {{ Str::limit($service->title, 50) }}</td></tr>
    </table>

    <div class="divider"></div>

    @if($service->jobcardDetail)
    <table>
        <tr><td class="label">KM Masuk</td><td>: {{ number_format($service->jobcardDetail->odometer_in, 0, ',', '.') }}</td></tr>
        @if($service->jobcardDetail->odometer_out)
        <tr><td class="label">KM Keluar</td><td>: {{ number_format($service->jobcardDetail->odometer_out, 0, ',', '.') }}</td></tr>
        @endif
        <tr><td class="label">Tgl Masuk</td><td>: {{ $service->jobcardDetail->in_date?->format('d M Y') ?? '-' }}</td></tr>
        @if($service->jobcardDetail->out_date)
        <tr><td class="label">Tgl Keluar</td><td>: {{ $service->jobcardDetail->out_date?->format('d M Y') ?? '-' }}</td></tr>
        @endif
    </table>
    @endif

    <div class="divider"></div>

    <div class="footer">
        <p>*** GATE PASS - Simpan sebagai bukti ***</p>
        <p>Petugas: _______________ &nbsp;&nbsp; Pelanggan: _______________</p>
    </div>
</body>
</html>
