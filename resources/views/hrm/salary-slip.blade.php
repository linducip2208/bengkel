<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Slip Gaji {{ $salary->user->name }}</title>
<style>
    body { font-family: sans-serif; font-size: 11pt; color: #333; }
    h2 { text-align: center; margin: 0; }
    .sub { text-align: center; color: #666; margin-bottom: 1.5rem; }
    table { width: 100%; border-collapse: collapse; }
    table.info { margin-bottom: 1rem; }
    table.info td { padding: 4px 8px; }
    table.detail th, table.detail td { padding: 6px 8px; border-bottom: 1px solid #ccc; }
    table.detail th { background: #f3f4f6; text-align: left; }
    .total { font-weight: bold; background: #fef3c7; }
    .text-right { text-align: right; }
    .no-print { margin-bottom: 1rem; display: flex; gap: .5rem; }
    .no-print a, .no-print button { padding: .4rem .9rem; border-radius: 6px; border: 1px solid #ccc; background: #fff; cursor: pointer; text-decoration: none; color: #333; font-size: 10pt; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
    <div class="no-print">
        <a href="{{ route('hrm.salary') }}">&larr; Kembali</a>
        <button onclick="window.print()">Cetak Slip</button>
    </div>
    <h2>SLIP GAJI</h2>
    @if(!empty($appSettings['logo']))
    <div style="text-align: center; margin-bottom: .5rem;"><img src="{{ asset('storage/' . $appSettings['logo']) }}" alt="Logo" style="height: 42px; width: auto;"></div>
    @endif
    <div class="sub">{{ $appSettings['name'] ?? config('app.name') }}@if(!empty($appSettings['address'])) — {{ $appSettings['address'] }}@endif<br>Periode {{ \Carbon\Carbon::create()->month($salary->period_month)->translatedFormat('F') }} {{ $salary->period_year }} &middot; Status: {{ $salary->paid_at ? 'Lunas' : 'Draft' }}</div>

    <table class="info">
        <tr><td width="30%"><strong>Nama Karyawan</strong></td><td>: {{ $salary->user->name }}</td></tr>
        <tr><td><strong>Posisi</strong></td><td>: {{ $salary->user->position ?? '-' }}</td></tr>
        <tr><td><strong>Email</strong></td><td>: {{ $salary->user->email }}</td></tr>
        <tr><td><strong>Hari Hadir</strong></td><td>: {{ $salary->days_present }} hari</td></tr>
        <tr><td><strong>Hari Tidak Hadir</strong></td><td>: {{ $salary->days_absent }} hari</td></tr>
    </table>

    <table class="detail">
        <thead><tr><th>Komponen</th><th class="text-right">Jumlah</th></tr></thead>
        <tbody>
            <tr><td>Gaji Pokok</td><td class="text-right">Rp {{ number_format($salary->base_salary, 0, ',', '.') }}</td></tr>
            <tr><td>Komisi Service</td><td class="text-right">Rp {{ number_format($salary->commission_total, 0, ',', '.') }}</td></tr>
            @if((float) ($salary->allowance ?? 0) != 0)<tr><td>Tunjangan</td><td class="text-right">Rp {{ number_format($salary->allowance, 0, ',', '.') }}</td></tr>@endif
            @if((float) ($salary->deduction ?? 0) != 0)<tr><td>Potongan</td><td class="text-right">- Rp {{ number_format($salary->deduction, 0, ',', '.') }}</td></tr>@endif
            <tr class="total"><td>TOTAL DITERIMA</td><td class="text-right">Rp {{ number_format($salary->net_salary, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <p style="margin-top: 3rem; text-align: right;">
        {{ now()->format('d M Y') }}<br><br><br>
        ___________________________<br>
        HRD / Admin Bengkel
    </p>
</body>
</html>
