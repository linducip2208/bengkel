<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #1a56db; padding-bottom: 15px; margin-bottom: 20px; }
        .header h2 { color: #1a56db; margin: 0 0 5px; }
        .header p { margin: 2px 0; font-size: 12px; color: #666; }
        .info { margin-bottom: 20px; }
        .info table { width: 100%; }
        .info td { vertical-align: top; padding: 5px; }
        .label { font-size: 11px; color: #888; }
        table.items { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.items th { background: #1a56db; color: #fff; padding: 8px; text-align: left; font-size: 12px; }
        table.items td { padding: 8px; border-bottom: 1px solid #ddd; }
        table.items .text-end { text-align: right; }
        table.items .text-center { text-align: center; }
        .totals { margin-top: 10px; }
        .totals table { width: 100%; }
        .totals td { padding: 5px 10px; }
        .totals .text-end { text-align: right; }
        .totals .total-row { border-top: 2px solid #1a56db; font-weight: bold; font-size: 14px; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 15px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-lunas { background: #d4edda; color: #155724; }
        .status-sebagian { background: #fff3cd; color: #856404; }
        .status-belum { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="header">
    <h2>{{ config('app.name') }}</h2>
    <p>Jl. Otomotif No. 123, Jakarta | Telp: (021) 1234-5678 | Email: info@bengkelpaten.com</p>
    <h3>INVOICE</h3>
</div>

<div class="info">
    <table>
        <tr>
            <td width="50%">
                <div class="label">Kepada:</div>
                <strong>{{ $invoice->customer->name ?? '-' }}</strong><br>
                {{ $invoice->customer->phone ?? '' }}<br>
                {{ $invoice->customer->address ?? '' }}
            </td>
            <td width="50%" align="right">
                <table>
                    <tr><td class="label">No. Invoice</td><td style="padding-left:15px"><strong>{{ $invoice->invoice_number }}</strong></td></tr>
                    <tr><td class="label">Tanggal</td><td style="padding-left:15px">{{ $invoice->invoice_date->format('d M Y') }}</td></tr>
                    <tr><td class="label">Tipe</td><td style="padding-left:15px">{{ ucfirst($invoice->invoice_type) }}</td></tr>
                    <tr><td class="label">Status</td><td style="padding-left:15px">
                        @if ($invoice->status === 'full_paid')
                            <span class="status-badge status-lunas">LUNAS</span>
                        @elseif ($invoice->status === 'half_paid')
                            <span class="status-badge status-sebagian">DIBAYAR SEBAGIAN</span>
                        @else
                            <span class="status-badge status-belum">BELUM DIBAYAR</span>
                        @endif
                    </td></tr>
                    @if ($invoice->service)
                    <tr><td class="label">No. Service</td><td style="padding-left:15px">{{ $invoice->service->job_no }}</td></tr>
                    <tr><td class="label">Kendaraan</td><td style="padding-left:15px">{{ $invoice->service->vehicle?->number_plate }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>
</div>

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
                <td class="text-end">@money($item->total)</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        <tr><td width="65%" class="text-end">Subtotal</td><td width="35%" class="text-end">@money($invoice->subtotal)</td></tr>
        <tr><td class="text-end">Diskon</td><td class="text-end">- @money($invoice->discount)</td></tr>
        <tr><td class="text-end">Pajak</td><td class="text-end">@money($invoice->tax_amount)</td></tr>
        <tr class="total-row"><td class="text-end">Grand Total</td><td class="text-end">@money($invoice->grand_total)</td></tr>
        <tr><td class="text-end">Total Dibayar</td><td class="text-end">@money($totalPaid)</td></tr>
        <tr class="total-row"><td class="text-end">Sisa</td><td class="text-end">@money($remaining)</td></tr>
    </table>
</div>

@if ($invoice->paymentRecords->count() > 0)
    <h4 style="margin-top: 25px;">Riwayat Pembayaran</h4>
    <table class="items">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Metode</th>
                <th class="text-end">Jumlah</th>
                <th>Ref</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->paymentRecords as $payment)
                <tr>
                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                    <td>{{ $payment->paymentMethod?->name }}</td>
                    <td class="text-end">@money($payment->amount)</td>
                    <td>{{ $payment->reference_number }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if ($invoice->notes)
    <p style="margin-top: 20px;"><strong>Catatan:</strong> {{ $invoice->notes }}</p>
@endif

<div class="footer">
    Terima kasih telah mempercayakan perawatan kendaraan Anda di {{ config('app.name') }}.<br>
    Jl. Otomotif No. 123, Jakarta | Telp: (021) 1234-5678
</div>

</body>
</html>
