<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Service — {{ $service->vehicle?->number_plate ?? $service->job_no }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 15px; }
        .header { border-bottom: 2px solid #dc2626; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; }
        .header .logo { width: 60px; height: 60px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 26px; background: #fef2f2; color: #dc2626; }
        .header .info h2 { margin: 0; color: #dc2626; font-size: 18px; }
        .header .info p { margin: 2px 0; font-size: 10px; color: #666; }
        .header .title { text-align: right; flex: 1; }
        .header .title h3 { margin: 0; font-size: 20px; color: #dc2626; }
        .section { margin-bottom: 15px; }
        .section-title { background: #dc2626; color: #fff; padding: 5px 10px; font-size: 12px; font-weight: bold; margin-bottom: 8px; border-radius: 3px; }
        .info-table { width: 100%; }
        .info-table td { padding: 3px 8px; vertical-align: top; }
        .info-table .label { font-size: 10px; color: #888; }
        .info-table .value { font-size: 12px; }
        table.items { width: 100%; border-collapse: collapse; margin: 8px 0; }
        table.items th { background: #f1f5f9; color: #1e293b; padding: 6px 8px; text-align: left; font-size: 10px; font-weight: bold; border-bottom: 2px solid #dc2626; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals { width: 100%; margin-top: 10px; }
        .totals table { width: 35%; float: right; }
        .totals td { padding: 3px 10px; font-size: 11px; }
        .totals .total-row td { border-top: 2px solid #dc2626; font-weight: bold; font-size: 14px; color: #dc2626; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; clear: both; }
        .checklist { list-style: none; padding: 0; }
        .checklist li { padding: 3px 0; font-size: 11px; }
        .checklist li::before { content: "✓ "; color: #16a34a; font-weight: bold; }
    </style>
</head>
<body>

<div class="header">
    <div class="logo">&#x1F527;</div>
    <div class="info">
        <h2>{{ $settings['name'] ?? config('app.name') }}</h2>
        <p>{{ $settings['address'] ?? '' }} | Telp: {{ $settings['phone'] ?? '-' }}</p>
    </div>
    <div class="title">
        <h3>LAPORAN SERVICE</h3>
        <div style="font-size:13px;font-weight:bold;">No: {{ $service->job_no }}</div>
    </div>
</div>

{{-- KENDARAAN --}}
<div class="section">
    <div class="section-title">DATA KENDARAAN</div>
    <table class="info-table">
        <tr>
            <td width="33%"><span class="label">No Polisi</span><br><span class="value"><strong>{{ $service->vehicle?->number_plate ?? '-' }}</strong></span></td>
            <td width="33%"><span class="label">Merk / Type</span><br><span class="value">{{ $service->vehicle?->vehicleBrand?->vehicle_brand ?? '-' }} / {{ $service->vehicle?->model_name ?? '-' }}</span></td>
            <td width="33%"><span class="label">Tahun</span><br><span class="value">{{ $service->vehicle?->model_year ?? '-' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">No Rangka</span><br><span class="value">{{ $service->vehicle?->chassis_number ?? '-' }}</span></td>
            <td><span class="label">No Mesin</span><br><span class="value">{{ $service->vehicle?->engine_number ?? '-' }}</span></td>
            <td><span class="label">Warna</span><br><span class="value">{{ $service->vehicle?->color ?? '-' }}</span></td>
        </tr>
        <tr>
            <td><span class="label">KM Masuk</span><br><span class="value">{{ number_format($service->jobcardDetail?->odometer_in ?? 0, 0, ',', '.') }}</span></td>
            <td><span class="label">KM Keluar</span><br><span class="value">{{ number_format($service->jobcardDetail?->odometer_out ?? 0, 0, ',', '.') }}</span></td>
            <td><span class="label">Kategori</span><br><span class="value">{{ $service->repairCategory?->repair_category_name ?? '-' }}</span></td>
        </tr>
    </table>
</div>

{{-- PEMILIK --}}
<div class="section">
    <div class="section-title">DATA PEMILIK</div>
    <table class="info-table">
        <tr>
            <td width="50%"><span class="label">Nama</span><br><span class="value"><strong>{{ $service->customer?->name ?? '-' }}</strong></span></td>
            <td width="50%"><span class="label">Telepon</span><br><span class="value">{{ $service->customer?->phone ?? '-' }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="label">Alamat</span><br><span class="value">{{ $service->customer?->address ?? '-' }}</span></td>
        </tr>
    </table>
</div>

{{-- KELUHAN & PEKERJAAN --}}
<div class="section">
    <div class="section-title">KELUHAN &amp; PEKERJAAN</div>
    <table class="info-table">
        <tr>
            <td width="25%"><span class="label">Tanggal Masuk</span><br><span class="value">{{ $service->service_date->format('d M Y H:i') }}</span></td>
            <td width="25%"><span class="label">Tanggal Selesai</span><br><span class="value">{{ $service->completed_at?->format('d M Y H:i') ?? 'Belum selesai' }}</span></td>
            <td width="25%"><span class="label">Teknisi</span><br><span class="value">{{ $service->technicians->pluck('name')->implode(', ') ?: '-' }}</span></td>
            <td width="25%"><span class="label">Status</span><br><span class="value"><strong style="color:{{ $service->done_status >= 2 ? '#16a34a' : '#d97706' }}">{{ $service->status_label }}</strong></span></td>
        </tr>
    </table>
    <p style="margin-top:8px;background:#fef2f2;padding:10px;border-radius:5px;font-size:11px;">
        <strong>Keluhan:</strong> {{ $service->description ?? 'Tidak ada keluhan' }}<br>
        <strong>Judul Servis:</strong> {{ $service->title ?? '-' }}
    </p>
</div>

{{-- PART --}}
<div class="section">
    <div class="section-title">PENGGANTIAN PART</div>
    @php
        $partItems = $service->invoice?->items?->filter(fn($i) => $i->product_id) ?? collect();
        $serviceItems = $service->invoice?->items?->filter(fn($i) => !$i->product_id) ?? collect();
    @endphp
    @if($partItems->isNotEmpty())
    <table class="items">
        <thead><tr><th width="5%">#</th><th width="45%">Nama Part</th><th width="10%" class="text-center">Qty</th><th width="20%" class="text-end">Harga Satuan</th><th width="20%" class="text-end">Subtotal</th></tr></thead>
        <tbody>
            @foreach($partItems as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">@money($item->unit_price)</td>
                <td class="text-end">@money($item->total_price)</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color:#888;font-size:11px;">Tidak ada penggantian part.</p>
    @endif
</div>

{{-- JASA --}}
<div class="section">
    <div class="section-title">JASA PERBAIKAN</div>
    @if($serviceItems->isNotEmpty())
    <table class="items">
        <thead><tr><th width="5%">#</th><th width="45%">Jenis Pekerjaan</th><th width="10%" class="text-center">Qty</th><th width="20%" class="text-end">Harga Satuan</th><th width="20%" class="text-end">Subtotal</th></tr></thead>
        <tbody>
            @foreach($serviceItems as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">@money($item->unit_price)</td>
                <td class="text-end">@money($item->total_price)</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color:#888;font-size:11px;">{{ $service->invoice ? 'Semua item adalah part.' : 'Belum ada invoice.' }}</p>
    @endif
</div>

{{-- OBSERVASI --}}
@php $observations = $service->serviceObservationPoints ?? collect(); @endphp
@if($observations->isNotEmpty())
<div class="section">
    <div class="section-title">HASIL OBSERVASI</div>
    <ul class="checklist">
        @foreach($observations as $obs)
        <li>
            {{ $obs->observationPoint?->observation_point ?? 'Checklist item' }}
            @if($obs->observationPoint?->observationType)
                <small style="color:#888">({{ $obs->observationPoint->observationType->observation_type }})</small>
            @endif
            — <strong style="color:{{ $obs->status === 'pass' ? '#16a34a' : ($obs->status === 'fail' ? '#dc2626' : '#d97706') }}">{{ strtoupper($obs->status ?? '?') }}</strong>
            @if($obs->notes)<br><small style="color:#666">{{ $obs->notes }}</small>@endif
        </li>
        @endforeach
    </ul>
</div>
@endif

{{-- RINGKASAN BIAYA --}}
<div class="section">
    <div class="section-title">RINGKASAN BIAYA</div>
    <div class="totals">
        <table>
            <tr><td class="text-end">Sub Total Part</td><td width="35%" class="text-end">@money($partItems->sum('total_price'))</td></tr>
            <tr><td class="text-end">Sub Total Jasa</td><td class="text-end">@money($serviceItems->sum('total_price'))</td></tr>
            <tr><td class="text-end">PPN / Pajak</td><td class="text-end">@money($service->invoice?->tax_amount ?? 0)</td></tr>
            @if($service->invoice?->discount > 0)
            <tr><td class="text-end">Diskon</td><td class="text-end">- @money($service->invoice->discount)</td></tr>
            @endif
            <tr class="total-row"><td class="text-end">TOTAL</td><td class="text-end">@money($service->invoice?->grand_total ?? $service->charge ?? 0)</td></tr>
        </table>
    </div>
    <div style="clear:both;"></div>
</div>

<div class="footer">
    <strong>Terima kasih telah mempercayakan kendaraan Anda kepada kami.</strong><br>
    {{ $settings['name'] ?? config('app.name') }} — {{ $settings['address'] ?? '' }}<br>
    Telp: {{ $settings['phone'] ?? '-' }}
    @if(!empty($settings['bank_account']))
    | Rek: {{ $settings['bank_account'] }}
    @endif
    @if(!empty($settings['qris_available']))
    | QRIS Tersedia
    @endif
    <br>Laporan ini dicetak secara otomatis pada {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
