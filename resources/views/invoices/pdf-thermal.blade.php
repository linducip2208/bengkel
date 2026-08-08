<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 5mm; size: 80mm 297mm; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; margin: 0; padding: 0; width: 70mm; line-height: 1.3; }
        .center { text-align: center; }
        .right { text-align: right; }
        .dash { border-top: 1px dashed #000; margin: 6px 0; }
        .solid { border-top: 1px solid #000; margin: 6px 0; }
        h2 { font-size: 13px; margin: 2px 0; }
        h3 { font-size: 11px; margin: 2px 0; }
        .small { font-size: 9px; }
        table { width: 100%; }
        table td { padding: 1px 0; font-size: 11px; vertical-align: top; }
        table.items { width: 100%; margin: 4px 0; }
        table.items td { padding: 2px 0; }
        .bold { font-weight: bold; }
        .status-stamp { margin: 8px auto; text-align: center; border: 2px solid #000; display: inline-block; padding: 4px 20px; font-size: 13px; font-weight: bold; letter-spacing: 2px; }
    </style>
</head>
<body>

<div class="center">
    @if(!empty($settings['logo']) && file_exists(public_path('storage/' . $settings['logo'])))
        <img src="{{ public_path('storage/' . $settings['logo']) }}" style="max-width:55px;max-height:55px;margin-bottom:4px;"><br>
    @endif
    <h2>{{ $settings['name'] ?? config('app.name') }}</h2>
    <div class="small">{{ $settings['address'] ?? '' }}</div>
    <div class="small">Telp: {{ $settings['phone'] ?? '-' }}</div>
</div>

<div class="dash"></div>

<div class="center">
    <h3>INVOICE</h3>
    <div class="bold">{{ $invoice->invoice_number }}</div>
</div>

<div class="dash"></div>

<table>
    <tr><td>Tanggal</td><td class="right">{{ $invoice->invoice_date->format('d/m/Y') }}</td></tr>
    <tr><td>Tipe</td><td class="right">{{ ucfirst($invoice->invoice_type) }}</td></tr>
    @if($invoice->due_date)<tr><td>Jatuh Tempo</td><td class="right">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td></tr>@endif
</table>

<div class="dash"></div>

<div class="bold">Pelanggan:</div>
<div>{{ $invoice->customer->name ?? '-' }}</div>
<div class="small">{{ $invoice->customer->phone ?? '' }}</div>

@php $vehicle = $invoice->vehicle ?? $invoice->service?->vehicle ?? $invoice->sale?->vehicle; @endphp
<div class="dash"></div>
<div class="bold">Kendaraan:</div>
<div>{{ $vehicle->model_name ?? '-' }}</div>
<div>{{ $vehicle->number_plate ?? '-' }} &nbsp;KM: {{ number_format($invoice->service?->jobcardDetail?->odometer_in ?? $vehicle->odometer ?? 0, 0, ',', '.') }}</div>

<div class="dash"></div>

<table class="items">
    @foreach ($invoice->items as $idx => $item)
        <tr>
            <td width="30">{{ $idx + 1 }}.</td>
            <td>{{ $item->description }}</td>
        </tr>
        <tr>
            <td></td>
            <td>{{ $item->quantity }} x @money($item->unit_price) &nbsp; &nbsp; <span class="right bold">@money($item->total_price)</span></td>
        </tr>
    @endforeach
</table>

<div class="dash"></div>

<table>
    <tr><td>Subtotal</td><td class="right">@money($invoice->subtotal)</td></tr>
    @if($invoice->discount > 0)<tr><td>Diskon</td><td class="right">- @money($invoice->discount)</td></tr>@endif
    @if($invoice->tax_amount > 0)<tr><td>Pajak</td><td class="right">@money($invoice->tax_amount)</td></tr>@endif
</table>

<div class="solid"></div>

<table>
    <tr><td class="bold" style="font-size:13px;">TOTAL</td><td class="right bold" style="font-size:13px;">@money($invoice->grand_total)</td></tr>
    @if($totalPaid > 0)<tr><td>Dibayar</td><td class="right">@money($totalPaid)</td></tr>@endif
    @if($remaining > 0)<tr><td class="bold">Sisa</td><td class="right bold">@money($remaining)</td></tr>@endif
</table>

<div class="solid"></div>

<div class="center">
    @if ($invoice->status === 'full_paid')
        <span class="status-stamp">LUNAS</span>
    @elseif ($invoice->status === 'half_paid')
        <span class="status-stamp">DIBAYAR SEBAGIAN</span>
    @else
        <span class="status-stamp">BELUM DIBAYAR</span>
    @endif
</div>

@if ($invoice->paymentRecords->count() > 0)
<div class="dash"></div>
<div class="bold">Pembayaran:</div>
@foreach ($invoice->paymentRecords as $p)
    <table><tr><td>{{ $p->payment_date->format('d/m') }}</td><td>{{ $p->paymentMethod?->name }}</td><td class="right">@money($p->amount)</td></tr></table>
@endforeach
@endif

<div class="dash"></div>

@if($invoice->notes)
<div class="center small">{{ $invoice->notes }}</div>
<div class="dash"></div>
@endif

<div class="center">
    <div>Terima kasih atas kepercayaan Anda!</div>
    <div class="small">{{ $settings['name'] ?? config('app.name') }}</div>
    <div class="small">{{ $settings['phone'] ?? '' }}</div>
    @if(($settings['qris_available'] ?? '0') == '1')<div class="small">QRIS Tersedia</div>@endif
</div>

<div class="dash"></div>
<div class="center small">Dicetak: {{ now()->format('d/m/Y H:i') }}</div>

</body>
</html>
