<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 13px; color: #000; margin: 0; padding: 25px; }
        .header { border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
        .header table { width: 100%; }
        .header .logo-cell { width: 80px; vertical-align: top; }
        .header .logo { width: 70px; height: 70px; border: 1px solid #ccc; text-align: center; display: flex; align-items: center; justify-content: center; font-size: 28px; }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; text-transform: uppercase; letter-spacing: 1px; }
        .header .company-detail { font-size: 11px; line-height: 1.5; }
        .header .invoice-box { text-align: right; vertical-align: top; }
        .header .invoice-title { font-size: 26px; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 0; }
        .header .invoice-no { font-size: 14px; font-weight: bold; }
        .info-table { width: 100%; margin-bottom: 18px; }
        .info-table td { vertical-align: top; padding: 4px 8px; font-size: 12px; }
        .info-table .label { font-weight: bold; width: 130px; }
        .divider { border-top: 1px solid #000; margin: 5px 0 10px 0; }
        table.items { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.items thead { border-top: 2px solid #000; border-bottom: 2px solid #000; }
        table.items th { padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items td { padding: 7px 10px; border-bottom: 1px solid #ddd; font-size: 12px; }
        table.items tfoot { border-top: 2px solid #000; }
        table.items tfoot td { border-bottom: none; padding: 6px 10px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals-table { width: 50%; float: right; margin-top: 5px; }
        .totals-table td { padding: 3px 10px; font-size: 12px; }
        .totals-table .grand-total { font-size: 15px; font-weight: bold; border-top: 2px solid #000; border-bottom: 3px double #000; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .footer { margin-top: 35px; padding-top: 12px; border-top: 1px solid #000; font-size: 10px; line-height: 1.6; }
        .footer strong { font-size: 11px; }
        .stamp-area { float: right; margin-top: 30px; text-align: center; }
        .stamp-box { border: 1px solid #000; width: 180px; height: 80px; margin-top: 8px; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999; }
        .status-text { font-weight: bold; font-size: 12px; }
        .vehicle-row { background: #fff; }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td class="logo-cell">
                @if(!empty($settings['logo']) && file_exists(public_path('storage/' . $settings['logo'])))
                    <img src="{{ public_path('storage/' . $settings['logo']) }}" style="max-width:70px;max-height:70px;">
                @else
                    <div class="logo">&#x1F527;</div>
                @endif
            </td>
            <td>
                <h2>{{ $settings['name'] ?? config('app.name') }}</h2>
                <div class="company-detail">
                    {{ $settings['address'] ?? '' }}<br>
                    Telp: {{ $settings['phone'] ?? '-' }} &nbsp;|&nbsp; Email: {{ $settings['email'] ?? '-' }}<br>
                    NPWP: {{ $settings['tax_id'] ?? '-' }}
                </div>
            </td>
            <td class="invoice-box">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-no">{{ $invoice->invoice_number }}</div>
                <div style="font-size:11px;margin-top:4px;">
                    Tanggal: {{ $invoice->invoice_date->format('d M Y') }}
                    @if($invoice->due_date)<br>Jatuh Tempo: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}@endif
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="info-table">
    <table style="width:100%">
        <tr>
            <td class="label">Kepada Yth.</td>
            <td>:</td>
            <td><strong>{{ $invoice->customer->name ?? '-' }}</strong></td>
            <td class="label" style="text-align:right;padding-right:30px;">Status</td>
            <td>:</td>
            <td>
                <span class="status-text">
                    @if ($invoice->status === 'full_paid') LUNAS
                    @elseif ($invoice->status === 'half_paid') DIBAYAR SEBAGIAN
                    @else BELUM DIBAYAR
                    @endif
                </span>
            </td>
        </tr>
        <tr>
            <td class="label"></td>
            <td></td>
            <td>{{ $invoice->customer->phone ?? '' }}</td>
            <td class="label" style="text-align:right;padding-right:30px;">Tipe</td>
            <td>:</td>
            <td>{{ ucfirst($invoice->invoice_type) }}</td>
        </tr>
        <tr>
            <td class="label"></td>
            <td></td>
            <td style="font-size:11px;">{{ $invoice->customer->address ?? '' }}</td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
</div>

@php $vehicle = $invoice->service?->vehicle ?? $invoice->sale?->vehicle; @endphp
@if($vehicle || $invoice->service)
<div class="info-table">
    <table style="width:100%">
        <tr>
            <td class="label">Kendaraan</td><td>:</td><td>{{ $vehicle->model_name ?? '-' }}</td>
            <td class="label">No. Plat</td><td>:</td><td><strong>{{ $vehicle->number_plate ?? '-' }}</strong></td>
            <td class="label">No. Service</td><td>:</td><td>{{ $invoice->service?->job_no ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun</td><td>:</td><td>{{ $vehicle->model_year ?? '-' }}</td>
            <td class="label">KM</td><td>:</td><td>{{ number_format($invoice->service?->jobcardDetail?->odometer_in ?? $vehicle->odometer ?? 0, 0, ',', '.') }}</td>
            <td></td><td></td><td></td>
        </tr>
    </table>
</div>
@endif

<div class="divider"></div>

<table class="items">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="47%">Deskripsi</th>
            <th width="10%" class="text-center">Qty</th>
            <th width="18%" class="text-end">Harga Satuan</th>
            <th width="20%" class="text-end">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->items as $idx => $item)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-end">@money($item->unit_price)</td>
                <td class="text-end">@money($item->total_price)</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="clearfix">
    <table class="totals-table">
        <tr><td class="text-end">Subtotal</td><td width="30"></td><td class="text-end">@money($invoice->subtotal)</td></tr>
        @if($invoice->discount > 0)<tr><td class="text-end">Diskon</td><td></td><td class="text-end">- @money($invoice->discount)</td></tr>@endif
        @if($invoice->tax_amount > 0)<tr><td class="text-end">Pajak</td><td></td><td class="text-end">@money($invoice->tax_amount)</td></tr>@endif
        <tr class="grand-total"><td class="text-end">GRAND TOTAL</td><td></td><td class="text-end">@money($invoice->grand_total)</td></tr>
        @if($totalPaid > 0)<tr><td class="text-end">Total Dibayar</td><td></td><td class="text-end">@money($totalPaid)</td></tr>@endif
        @if($remaining > 0)<tr style="font-weight:bold;"><td class="text-end">Sisa Pembayaran</td><td></td><td class="text-end">@money($remaining)</td></tr>@endif
    </table>
</div>

@if ($invoice->paymentRecords->count() > 0)
<div class="clearfix" style="margin-top:20px;">
    <strong style="font-size:12px;">Riwayat Pembayaran</strong>
    <table class="items" style="margin-top:5px;">
        <thead><tr><th>Tanggal</th><th>Metode Pembayaran</th><th class="text-end">Jumlah</th><th>Referensi</th></tr></thead>
        <tbody>
            @foreach ($invoice->paymentRecords as $p)
                <tr><td>{{ $p->payment_date->format('d M Y') }}</td><td>{{ $p->paymentMethod?->name }}</td><td class="text-end">@money($p->amount)</td><td>{{ $p->reference_number }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="clearfix">
    <div class="stamp-area">
        <div>Hormat Kami,</div>
        <div class="stamp-box">Tanda Tangan<br>&amp; Stempel</div>
        <div style="font-size:11px;margin-top:5px;">{{ $settings['name'] ?? config('app.name') }}</div>
    </div>
</div>

<div class="footer clearfix">
    <strong>{{ $settings['name'] ?? config('app.name') }}</strong><br>
    {{ $settings['address'] ?? '' }} &nbsp;|&nbsp; Telp: {{ $settings['phone'] ?? '' }} &nbsp;|&nbsp; Email: {{ $settings['email'] ?? '' }}
    @if(!empty($settings['bank_account']))<br>Pembayaran melalui rekening: <strong>{{ $settings['bank_account'] }}</strong>@endif
    @if(($settings['qris_available'] ?? '0') == '1') &nbsp;|&nbsp; QRIS Tersedia @endif
    @if($invoice->notes)<br><br><em>Catatan: {{ $invoice->notes }}</em>@endif
</div>

</body>
</html>
