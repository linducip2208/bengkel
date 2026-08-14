<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Modern — {{ $invoice->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f1f5f9; font-family: Helvetica, Arial, sans-serif; }
        .preview-toolbar { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #e2e8f0; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .preview-toolbar .badge-template { font-size: 12px; padding: 5px 12px; }
        .preview-container { max-width: 210mm; margin: 30px auto; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,.10); border-radius: 4px; padding: 15px; }
        .header { border-bottom: 2px solid #1a56db; padding-bottom: 12px; margin-bottom: 15px; display: flex; align-items: center; gap: 15px; }
        .header .logo { width: 70px; height: 70px; border: 1px solid #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 28px; background: #f8fafc; color: #1a56db; overflow: hidden; }
        .header .logo img { max-width: 70px; max-height: 70px; }
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
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-lunas { background: #d4edda; color: #155724; }
        .status-sebagian { background: #fff3cd; color: #856404; }
        .status-belum { background: #f8d7da; color: #721c24; }
        .vehicle-info { background: #f8fafc; padding: 8px 12px; border-radius: 6px; margin-top: 8px; }
        .footer { margin-top: 25px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 12px; line-height: 1.6; }
    </style>
</head>
<body>
@php
    $settingsService = app(\App\Services\SettingsService::class);
    $invoiceSections = $settingsService->getInvoiceSections();
    $vehicle = $invoice->vehicle ?? $invoice->service?->vehicle ?? $invoice->sale?->vehicle;
@endphp
<div class="preview-toolbar">
    <div class="d-flex align-items-center gap-3">
        <strong>{{ $invoice->invoice_number }}</strong>
        <span class="badge-template bg-primary">Modern</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('invoices.pdf', ['invoice' => $invoice, 'template' => 'modern']) }}" class="btn btn-primary btn-sm"><i class="bi bi-download"></i> Download PDF</a>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Tutup</a>
    </div>
</div>

<div class="preview-container">
    @foreach ($invoiceSections as $section)
        @if ($section === 'company')
            <div class="header">
                <div class="logo">
                    @if(!empty($settings['logo']) && file_exists(public_path('storage/' . $settings['logo'])))
                        <img src="{{ asset('storage/' . $settings['logo']) }}">
                    @else
                        &#x1F527;
                    @endif
                </div>
                <div class="info">
                    <h2>{{ $settings['name'] ?? config('app.name') }}</h2>
                    <p>{{ $settings['address'] ?? 'Jl. Bengkel No. 1' }} | Telp: {{ $settings['phone'] ?? '-' }}</p>
                    <p>Email: {{ $settings['email'] ?? '-' }} | NPWP: {{ $settings['tax_id'] ?? '-' }}</p>
                </div>
                <div class="invoice-title">
                    <h3>INVOICE</h3>
                    <div style="font-size:14px;color:#1a56db;font-weight:bold;">{{ $invoice->invoice_number }}</div>
                </div>
            </div>
        @elseif ($section === 'customer')
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
                            @if($invoice->due_date)<tr><td class="section-title">Berlaku Sampai</td><td class="value"><strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</strong></td></tr>@endif
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
        @elseif ($section === 'items')
            <table class="items">
                <thead>
                    <tr><th width="5%">#</th><th width="45%">Deskripsi</th><th width="10%" class="text-center">Qty</th><th width="20%" class="text-end">Harga Satuan</th><th width="20%" class="text-end">Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $idx => $item)
                        <tr><td>{{ $idx + 1 }}</td><td>{{ $item->description }}</td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">@money($item->unit_price)</td><td class="text-end">@money($item->total_price)</td></tr>
                    @endforeach
                </tbody>
            </table>
        @elseif ($section === 'totals')
            <div class="totals">
                <table>
                    <tr><td width="65%" class="text-end">Subtotal</td><td width="35%" class="text-end">@money($invoice->subtotal)</td></tr>
                    @if($invoice->discount > 0)<tr><td class="text-end">Diskon{{ $invoice->discount_type === 'percent' ? ' ' . $invoice->discount_percent . '%' : '' }}</td><td class="text-end">- @money($invoice->discount)</td></tr>@endif
                    @if($invoice->tax_amount > 0)<tr><td class="text-end">Pajak</td><td class="text-end">@money($invoice->tax_amount)</td></tr>@endif
                    <tr class="total-row"><td class="text-end">Grand Total</td><td class="text-end">@money($invoice->grand_total)</td></tr>
                    @if($totalPaid > 0)<tr><td class="text-end">Total Dibayar</td><td class="text-end">@money($totalPaid)</td></tr>@endif
                    @if($remaining > 0)<tr class="total-row"><td class="text-end">Sisa Pembayaran</td><td class="text-end">@money($remaining)</td></tr>@endif
                </table>
            </div>
        @elseif ($section === 'payments')
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
        @elseif ($section === 'notes')
            @if ($invoice->notes)
                <div style="margin-top: 15px;">
                    <div class="section-title">Catatan</div>
                    <div class="value">{{ $invoice->notes }}</div>
                </div>
            @endif
        @elseif ($section === 'footer')
            <div class="footer">
                <strong>Terima kasih atas kepercayaan Anda!</strong><br>
                Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.<br>
                {{ $settings['name'] ?? config('app.name') }} &mdash; {{ $settings['phone'] ?? '' }} &mdash; {{ $settings['address'] ?? '' }}
                @if(!empty($settings['bank_account']))<br>Rekening: {{ $settings['bank_account'] }}@endif
                @if(($settings['qris_available'] ?? '0') == '1') | QRIS Tersedia @endif
            </div>
        @endif
    @endforeach
</div>
</body>
</html>
