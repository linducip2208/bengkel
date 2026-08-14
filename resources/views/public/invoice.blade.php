<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }} — {{ $settings['name'] ?? config('app.name') }}</title>
    <meta name="robots" content="noindex">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --accent: {{ $accentColor }};
        }
        body {
            font-family: '{{ $font }}', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            padding: 1.5rem 1rem;
            color: #1f2937;
        }
        .invoice-sheet {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 40px -12px rgba(15, 23, 42, .12);
        }
        .invoice-header {
            background: var(--accent);
            color: #fff;
            padding: 1.5rem 1.75rem;
        }
        .invoice-header .brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        .invoice-header .brand .logo-box {
            width: 54px;
            height: 54px;
            background: rgba(255, 255, 255, .18);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .invoice-header .brand .logo-box img { max-width: 46px; max-height: 46px; }
        .invoice-header .company-name { font-size: 1.25rem; font-weight: 700; }
        .invoice-header .company-meta { font-size: .82rem; opacity: .92; line-height: 1.5; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 1.4rem; font-weight: 800; letter-spacing: .5px; margin: 0; }
        .invoice-body { padding: 1.75rem; }
        .section-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; font-weight: 600; margin-bottom: .15rem; }
        table.items { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        table.items th {
            background: var(--accent);
            color: #fff;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            padding: .6rem .75rem;
            text-align: left;
        }
        table.items td { padding: .6rem .75rem; border-bottom: 1px solid #e2e8f0; font-size: .88rem; }
        .totals { margin-top: .5rem; }
        .totals td { padding: .28rem .5rem; font-size: .88rem; }
        .totals .grand td { border-top: 2px solid var(--accent); font-weight: 700; font-size: 1.05rem; }
        .badge-paid { background: #dcfce7; color: #15803d; }
        .badge-partial { background: #fef9c3; color: #a16207; }
        .badge-unpaid { background: #fee2e2; color: #b91c1c; }
        .action-bar { display: flex; gap: .5rem; justify-content: flex-end; margin-top: 1.5rem; }
        .btn-accent { background: var(--accent); color: #fff; border: none; }
        .btn-accent:hover { color: #fff; filter: brightness(1.05); }
        .footer-note { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: .78rem; color: #64748b; text-align: center; line-height: 1.6; }
        @media print {
            body { background: #fff; padding: 0; }
            .invoice-sheet { box-shadow: none; border-radius: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="invoice-sheet">
        <div class="invoice-header">
            <div class="row align-items-start">
                <div class="col-8">
                    <div class="brand">
                        <div class="logo-box">
                            @if(!empty($settings['logo']) && file_exists(public_path('storage/' . $settings['logo'])))
                                <img src="{{ asset('storage/' . $settings['logo']) }}" alt="logo">
                            @else
                                <i class="bi bi-tools" style="font-size:1.5rem;"></i>
                            @endif
                        </div>
                        <div>
                            <div class="company-name">{{ $settings['name'] ?? config('app.name') }}</div>
                            <div class="company-meta">
                                @if(!empty($settings['address'])){{ $settings['address'] }}<br>@endif
                                @if(!empty($settings['phone']))Telp: {{ $settings['phone'] }}@endif
                                @if(!empty($settings['email'])) &middot; {{ $settings['email'] }}@endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="invoice-title">
                        <h1>INVOICE</h1>
                        <div style="font-size:.9rem;font-weight:600;">{{ $invoice->invoice_number }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="invoice-body">
            <div class="row g-3">
                <div class="col-sm-7">
                    <div class="section-label">Kepada / Pelanggan</div>
                    <div class="fw-semibold">{{ $invoice->customer->name ?? '-' }}</div>
                    @if($invoice->customer?->phone)<div>{{ $invoice->customer->phone }}</div>@endif
                    @if($invoice->customer?->address)<div class="text-muted small">{{ $invoice->customer->address }}</div>@endif
                </div>
                <div class="col-sm-5">
                    <div class="row">
                        <div class="col-6"><div class="section-label">Tanggal</div><div>{{ $invoice->invoice_date->format('d M Y') }}</div></div>
                        <div class="col-6"><div class="section-label">Status</div>
                            @if ($invoice->status === 'full_paid')
                                <span class="badge badge-paid">Lunas</span>
                            @elseif ($invoice->status === 'half_paid')
                                <span class="badge badge-partial">Dibayar Sebagian</span>
                            @else
                                <span class="badge badge-unpaid">Belum Dibayar</span>
                            @endif
                        </div>
                    </div>
                    @if($invoice->due_date)
                    <div class="mt-2"><div class="section-label">Jatuh Tempo</div><div>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</div></div>
                    @endif
                </div>
            </div>

            <table class="items">
                <thead>
                    <tr>
                        <th style="width:5%">#</th>
                        <th style="width:45%">Deskripsi</th>
                        <th style="width:10%;text-align:center">Qty</th>
                        <th style="width:20%;text-align:right">Harga</th>
                        <th style="width:20%;text-align:right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoice->items as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">@money($item->unit_price)</td>
                            <td class="text-end">@money($item->total_price)</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Tidak ada item.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="totals" style="width:100%">
                <tr><td class="text-end" style="width:70%">Subtotal</td><td class="text-end" style="width:30%">@money($invoice->subtotal)</td></tr>
                @if($invoice->discount > 0)<tr><td class="text-end">Diskon{{ $invoice->discount_type === 'percent' ? ' ' . $invoice->discount_percent . '%' : '' }}</td><td class="text-end text-danger">- @money($invoice->discount)</td></tr>@endif
                @if($invoice->tax_amount > 0)<tr><td class="text-end">Pajak</td><td class="text-end">@money($invoice->tax_amount)</td></tr>@endif
                <tr class="grand"><td class="text-end">Grand Total</td><td class="text-end">@money($invoice->grand_total)</td></tr>
                @if($totalPaid > 0)<tr><td class="text-end text-success">Total Dibayar</td><td class="text-end text-success">@money($totalPaid)</td></tr>@endif
                @if($remaining > 0)<tr class="grand"><td class="text-end">Sisa Pembayaran</td><td class="text-end">@money($remaining)</td></tr>@endif
            </table>

            @if ($invoice->paymentRecords->count() > 0)
                <h6 class="mt-4 mb-2">Riwayat Pembayaran</h6>
                <table class="items">
                    <thead><tr><th>Tanggal</th><th>Metode</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        @foreach ($invoice->paymentRecords as $p)
                            <tr>
                                <td>{{ $p->payment_date->format('d M Y') }}</td>
                                <td>{{ $p->paymentMethod?->name ?? '-' }}</td>
                                <td class="text-end">@money($p->amount)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="action-bar no-print">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
                @if($remaining > 0 && !empty($settings['phone']))
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings['phone']) }}" class="btn btn-success" target="_blank"><i class="bi bi-whatsapp me-1"></i>Hubungi</a>
                @endif
            </div>

            <div class="footer-note">
                Terima kasih atas kepercayaan Anda.<br>
                {{ $settings['name'] ?? config('app.name') }}
                @if(!empty($settings['address'])) &middot; {{ $settings['address'] }} @endif
                @if(!empty($settings['phone'])) &middot; {{ $settings['phone'] }} @endif
            </div>
        </div>
    </div>
</body>
</html>
