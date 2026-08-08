<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Minimal — {{ $invoice->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #fafafa; font-family: 'Inter', Helvetica, Arial, sans-serif; }
        .preview-toolbar { position: sticky; top: 0; z-index: 100; background: #fff; border-bottom: 1px solid #e8e8e8; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
        .preview-toolbar .badge-template { font-size: 12px; padding: 5px 12px; }
        .preview-container { max-width: 200mm; margin: 30px auto; background: #fff; box-shadow: 0 2px 16px rgba(0,0,0,.06); border-radius: 12px; padding: 35px 40px; line-height: 1.6; }
        .top-bar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .top-bar .brand { font-size: 22px; font-weight: 700; letter-spacing: -0.5px; color: #111; }
        .top-bar .brand-sub { font-size: 11px; color: #888; margin-top: 2px; }
        .top-bar .invoice-label { text-align: right; }
        .top-bar .tag { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .tag-lunas { background: #e8f5e9; color: #2e7d32; }
        .tag-sebagian { background: #fff8e1; color: #f57f17; }
        .tag-belum { background: #fce4ec; color: #c62828; }
        .invoice-number { font-size: 12px; color: #999; margin-top: 6px; font-weight: 400; }
        .two-col { display: flex; gap: 50px; margin-bottom: 30px; }
        .two-col .col { flex: 1; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1.2px; color: #aaa; margin-bottom: 2px; font-weight: 600; }
        .value { font-size: 13px; font-weight: 500; margin-bottom: 8px; }
        .value-sm { font-size: 11px; color: #666; }
        table.items { width: 100%; border-collapse: collapse; margin: 25px 0; }
        table.items thead th { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #999; padding: 10px 0; border-bottom: 1px solid #e0e0e0; font-weight: 600; }
        table.items tbody td { padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-left: auto; width: 280px; margin-top: 5px; }
        .totals .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; }
        .totals .row.total { font-size: 16px; font-weight: 700; padding-top: 10px; margin-top: 5px; border-top: 2px solid #1a1a1a; }
        .totals .row.paid { font-size: 12px; color: #2e7d32; }
        .footer { margin-top: 40px; padding-top: 15px; border-top: 1px solid #e8e8e8; display: flex; justify-content: space-between; align-items: flex-end; font-size: 10px; color: #999; }
        .footer .note { font-size: 10px; color: #aaa; font-style: italic; }
        .payment-history { margin-top: 25px; }
        .payment-history h4 { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin-bottom: 8px; font-weight: 600; }
        .payment-history table { width: 100%; border-collapse: collapse; }
        .payment-history td { padding: 3px 0; font-size: 11px; border-bottom: 1px solid #f5f5f5; }
    </style>
</head>
<body>
<div class="preview-toolbar">
    <div class="d-flex align-items-center gap-3">
        <strong>{{ $invoice->invoice_number }}</strong>
        <span class="badge-template bg-secondary">Minimal Clean</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('invoices.pdf', ['invoice' => $invoice, 'template' => 'minimal']) }}" class="btn btn-secondary btn-sm"><i class="bi bi-download"></i> Download PDF</a>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Tutup</a>
    </div>
</div>

<div class="preview-container">
    <div class="top-bar">
        <div style="display:flex;align-items:center;gap:14px;">
            @if(!empty($settings['logo']) && file_exists(public_path('storage/' . $settings['logo'])))
                <img src="{{ asset('storage/' . $settings['logo']) }}" style="width:40px;height:40px;border-radius:6px;">
            @endif
            <div>
                <div class="brand">{{ $settings['name'] ?? config('app.name') }}</div>
                <div class="brand-sub">{{ $settings['address'] ?? '' }} &nbsp;·&nbsp; {{ $settings['phone'] ?? '' }} &nbsp;·&nbsp; {{ $settings['email'] ?? '' }}</div>
            </div>
        </div>
        <div class="invoice-label">
            <span class="tag {{ $invoice->status === 'full_paid' ? 'tag-lunas' : ($invoice->status === 'half_paid' ? 'tag-sebagian' : 'tag-belum') }}">
                {{ $invoice->status === 'full_paid' ? 'Lunas' : ($invoice->status === 'half_paid' ? 'Dibayar Sebagian' : 'Belum Dibayar') }}
            </span>
            <div class="invoice-number">Invoice #{{ $invoice->invoice_number }}</div>
        </div>
    </div>

    <div class="two-col">
        <div class="col">
            <div class="label">Ditagihkan Kepada</div>
            <div class="value">{{ $invoice->customer->name ?? '-' }}</div>
            <div class="value-sm">{{ $invoice->customer->phone ?? '' }}</div>
            <div class="value-sm">{{ $invoice->customer->address ?? '' }}</div>
        </div>
        <div class="col">
            <div class="label">Detail Invoice</div>
            <div class="value">Tanggal: <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
            @if($invoice->due_date)<div class="value-sm">Jatuh Tempo: <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</strong></div>@endif
            <div class="value-sm">Tipe: {{ ucfirst($invoice->invoice_type) }}</div>
            @php $vehicle = $invoice->service?->vehicle ?? $invoice->sale?->vehicle; @endphp
            <div class="value-sm"><strong>Kendaraan:</strong> {{ $vehicle->model_name ?? '-' }} · {{ $vehicle->number_plate ?? '-' }}
                · KM {{ number_format($invoice->service?->jobcardDetail?->odometer_in ?? $vehicle->odometer ?? 0, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <table class="items">
        <thead><tr><th width="8%" class="text-center">#</th><th width="42%">Deskripsi</th><th width="10%" class="text-center">Qty</th><th width="18%" class="text-end">Harga</th><th width="22%" class="text-end">Jumlah</th></tr></thead>
        <tbody>
            @foreach ($invoice->items as $idx => $item)
                <tr><td class="text-center" style="color:#bbb;">{{ $idx + 1 }}</td><td>{{ $item->description }}</td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">@money($item->unit_price)</td><td class="text-end" style="font-weight:500;">@money($item->total_price)</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span style="color:#888;">Subtotal</span><span>@money($invoice->subtotal)</span></div>
        @if($invoice->discount > 0)<div class="row"><span style="color:#888;">Diskon</span><span>- @money($invoice->discount)</span></div>@endif
        @if($invoice->tax_amount > 0)<div class="row"><span style="color:#888;">Pajak</span><span>@money($invoice->tax_amount)</span></div>@endif
        <div class="row total"><span>Total</span><span>@money($invoice->grand_total)</span></div>
        @if($totalPaid > 0)<div class="row paid"><span>Dibayar</span><span>- @money($totalPaid)</span></div>@endif
        @if($remaining > 0)<div class="row" style="font-weight:600;"><span>Sisa</span><span>@money($remaining)</span></div>@endif
    </div>

    @if ($invoice->paymentRecords->count() > 0)
    <div class="payment-history">
        <h4>Riwayat Pembayaran</h4>
        <table>
            @foreach ($invoice->paymentRecords as $p)
                <tr><td>{{ $p->payment_date->format('d M Y') }}</td><td>{{ $p->paymentMethod?->name }}</td><td class="text-end" style="font-weight:500;">@money($p->amount)</td><td class="text-end" style="color:#aaa;font-size:10px;">{{ $p->reference_number }}</td></tr>
            @endforeach
        </table>
    </div>
    @endif

    <div class="footer">
        <div>
            {{ $settings['name'] ?? config('app.name') }} &nbsp;·&nbsp; {{ $settings['address'] ?? '' }}
            @if(!empty($settings['bank_account']))<br>Rekening: {{ $settings['bank_account'] }}@endif
            @if(($settings['qris_available'] ?? '0') == '1') &nbsp;·&nbsp; QRIS Tersedia @endif
        </div>
        <div class="note">@if($invoice->notes){{ $invoice->notes }} · @endif Terima kasih</div>
    </div>
</div>
</body>
</html>
