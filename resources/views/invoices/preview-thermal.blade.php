<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Thermal — {{ $invoice->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #d4d4d4; font-family: 'Courier New', Courier, monospace; }
        .preview-toolbar { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #ccc; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .preview-toolbar .badge-template { font-size: 12px; padding: 5px 12px; }
        .receipt { max-width: 260px; margin: 30px auto; background: #fffef7; box-shadow: 0 2px 8px rgba(0,0,0,.12); border-radius: 2px; padding: 18px 16px; font-size: 11px; color: #000; line-height: 1.3; }
        .center { text-align: center; }
        .right { text-align: right; }
        .dash { border-top: 1px dashed #000; margin: 6px 0; }
        .solid { border-top: 1px solid #000; margin: 6px 0; }
        h2 { font-size: 13px; margin: 2px 0; }
        h3 { font-size: 11px; margin: 2px 0; }
        .small { font-size: 9px; }
        table { width: 100%; }
        table td { padding: 1px 0; font-size: 11px; vertical-align: top; }
        .bold { font-weight: bold; }
        .status-stamp { margin: 8px auto; text-align: center; border: 2px solid #000; display: inline-block; padding: 4px 20px; font-size: 13px; font-weight: bold; letter-spacing: 2px; }
        .logo-receipt { max-width: 48px; max-height: 48px; margin-bottom: 4px; }
    </style>
</head>
<body>
<div class="preview-toolbar">
    <div class="d-flex align-items-center gap-3">
        <strong>{{ $invoice->invoice_number }}</strong>
        <span class="badge-template bg-success">Thermal / Struk</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('invoices.pdf', ['invoice' => $invoice, 'template' => 'thermal']) }}" class="btn btn-success btn-sm"><i class="bi bi-download"></i> Download PDF</a>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Tutup</a>
    </div>
</div>

<div class="receipt">
    <div class="center">
        @if(!empty($settings['logo']) && file_exists(public_path('storage/' . $settings['logo'])))
            <img src="{{ asset('storage/' . $settings['logo']) }}" class="logo-receipt"><br>
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

    <table>
        @foreach ($invoice->items as $idx => $item)
            <tr><td width="30">{{ $idx + 1 }}.</td><td>{{ $item->description }}</td></tr>
            <tr><td></td><td>{{ $item->quantity }} x @money($item->unit_price) &nbsp; &nbsp; <span class="right bold">@money($item->total_price)</span></td></tr>
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
</div>
</body>
</html>
