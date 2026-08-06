<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #333; margin: 0; padding: 15px; }
        .header { border-bottom: 2px solid #1a56db; padding-bottom: 12px; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; }
        .header .logo { width: 70px; height: 70px; border: 1px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 28px; background: #f8fafc; color: #1a56db; }
        .header .info h2 { margin: 0; color: #1a56db; font-size: 20px; }
        .header .info p { margin: 2px 0; font-size: 11px; color: #666; }
        .header .invoice-title { text-align: right; flex: 1; }
        .header .invoice-title h3 { margin: 0; font-size: 22px; color: #1a56db; }
        .body-table { width: 100%; margin-bottom: 15px; }
        .body-table td { vertical-align: top; padding: 5px 10px; }
        .section-title { font-size: 11px; color: #888; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 13px; }
        .value strong { font-size: 14px; }
        table.items { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.items th { background: #1a56db; color: #fff; padding: 7px 10px; text-align: left; font-size: 11px; }
        table.items td { padding: 7px 10px; border-bottom: 1px solid #ddd; font-size: 12px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 10px; }
        .totals table { width: 100%; }
        .totals td { padding: 4px 10px; font-size: 12px; }
        .totals .total-row td { border-top: 2px solid #1a56db; font-weight: bold; font-size: 14px; padding-top: 6px; }
        .footer { margin-top: 25px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 12px; line-height: 1.6; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-lunas { background: #d4edda; color: #155724; }
        .status-sebagian { background: #fff3cd; color: #856404; }
        .status-belum { background: #f8d7da; color: #721c24; }
        .vehicle-info { background: #f8fafc; padding: 8px 12px; border-radius: 6px; margin-top: 8px; }
        .vehicle-info td { padding: 2px 15px 2px 0; font-size: 12px; }
    </style>
</head>
<body>

<div class="header">
    <div class="logo">&#x1F527;</div>
    <div class="info">
        <h2>{{ config('app.name') }}</h2>
        <p>{{ $settings['address'] ?? 'Jl. Bengkel No. 1' }} | Telp: {{ $settings['phone'] ?? '-' }}</p>
        <p>Email: {{ $settings['email'] ?? '-' }} | NPWP: {{ $settings['tax_id'] ?? '-' }}</p>
    </div>
    <div class="invoice-title">
        <h3>INVOICE</h3>
        <div style="font-size:14px;color:#1a56db;font-weight:bold;">{{ $invoice->invoice_number }}</div>
    </div>
</div>

<table class="body-table">
    <tr>
        <td width="55%">
            <div class="section-title">Kepada / Pelanggan</div>
            <div class="value"><strong>{{ $invoice->customer->name ?? '-' }}</strong></div>
            <div class="value">{{ $invoice->customer->phone ?? '' }}</div>
            <div class="value" style="font-size:11px;">{{ $invoice->customer->address ?? '' }}</div>
        </td>
        <td width="45%">
            <table style="width:100%">
                <tr><td class="section-title" width="35%">Tgl Invoice</td><td class="value">{{ $invoice->invoice_date->format('d M Y') }}</td></tr>
                <tr><td class="section-title">Tipe</td><td class="value">{{ ucfirst($invoice->invoice_type) }}</td></tr>
                <tr><td class="section-title">Status</td><td class="value">
                    @if ($invoice->status === 'full_paid') <span class="status-badge status-lunas">LUNAS</span>
                    @elseif ($invoice->status === 'half_paid') <span class="status-badge status-sebagian">DIBAYAR SEBAGIAN</span>
                    @else <span class="status-badge status-belum">BELUM DIBAYAR</span>
                    @endif
                </td></tr>
            </table>
        </td>
    </tr>
</table>

@php $vehicle = $invoice->service?->vehicle ?? $invoice->sale?->vehicle; @endphp
@if($vehicle || $invoice->service)
<table class="vehicle-info" style="width:100%">
    <tr>
        <td class="section-title">Jenis Kendaraan</td>
        <td class="section-title">No. Plat</td>
        <td class="section-title">Tahun</td>
        <td class="section-title">KM</td>
        <td class="section-title">No. Service</td>
    </tr>
    <tr>
        <td class="value"><strong>{{ $vehicle->model_name ?? '-' }}</strong></td>
        <td class="value"><strong>{{ $vehicle->number_plate ?? '-' }}</strong></td>
        <td class="value">{{ $vehicle->model_year ?? '-' }}</td>
        <td class="value">{{ number_format($invoice->service?->jobcardDetail?->odometer_in ?? $vehicle->odometer ?? 0, 0, ',', '.') }}</td>
        <td class="value">{{ $invoice->service?->job_no ?? '-' }}</td>
    </tr>
</table>
@endif

<table class="items">
    <thead>
        <tr>
            <th width="5%">#</th>
            <th width="45%">Deskripsi</th>
            <th width="10%" class="text-center">Qty</th>
            <th width="20%" class="text-end">Harga Satuan</th>
            <th width="20%" class="text-end">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->items as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">@money($item->unit_price)</td>
                <td class="text-end">@money($item->total_price)</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        <tr><td width="65%" class="text-end">Subtotal</td><td width="35%" class="text-end">@money($invoice->subtotal)</td></tr>
        @if($invoice->discount > 0)<tr><td class="text-end">Diskon</td><td class="text-end">- @money($invoice->discount)</td></tr>@endif
        @if($invoice->tax_amount > 0)<tr><td class="text-end">Pajak</td><td class="text-end">@money($invoice->tax_amount)</td></tr>@endif
        <tr class="total-row"><td class="text-end">Grand Total</td><td class="text-end">@money($invoice->grand_total)</td></tr>
        @if($totalPaid > 0)<tr><td class="text-end">Total Dibayar</td><td class="text-end">@money($totalPaid)</td></tr>@endif
        @if($remaining > 0)<tr class="total-row"><td class="text-end">Sisa Pembayaran</td><td class="text-end">@money($remaining)</td></tr>@endif
    </table>
</div>

@if ($invoice->paymentRecords->count() > 0)
    <h4 style="margin-top: 20px; font-size: 13px;">Riwayat Pembayaran</h4>
    <table class="items">
        <thead><tr><th>Tanggal</th><th>Metode</th><th class="text-end">Jumlah</th><th>Ref</th></tr></thead>
        <tbody>
            @foreach ($invoice->paymentRecords as $p)
                <tr><td>{{ $p->payment_date->format('d M Y') }}</td><td>{{ $p->paymentMethod?->name }}</td><td class="text-end">@money($p->amount)</td><td>{{ $p->reference_number }}</td></tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">
    <strong>Terima kasih atas kepercayaan Anda!</strong><br>
    @if($invoice->notes){{ $invoice->notes }}<br>@endif
    Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.<br>
    {{ $settings['name'] ?? config('app.name') }} &mdash; {{ $settings['phone'] ?? '' }}
</div>

</body>
</html>
