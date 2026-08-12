<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }} — Customer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>body{background:#f1f5f9;min-height:100vh;padding:1.5rem} .container{max-width:800px} .badge-status{padding:6px 12px}</style>
</head>
<body>
<div class="container">
    <div class="mb-3"><a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a></div>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Invoice #{{ $invoice->invoice_number }}</h5>
            <span class="badge bg-{{ $invoice->payment_status >= 2 ? 'success' : ($invoice->payment_status >= 1 ? 'warning' : 'danger') }}">
                {{ $invoice->payment_status >= 2 ? 'Lunas' : ($invoice->payment_status >= 1 ? 'Sebagian' : 'Belum Bayar') }}
            </span>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-6"><small class="text-muted">Tanggal</small><br><strong>{{ $invoice->invoice_date->format('d M Y') }}</strong></div>
                <div class="col-6"><small class="text-muted">Tipe</small><br><strong>{{ ucfirst($invoice->invoice_type) }}</strong></div>
            </div>
            <table class="table table-sm">
                <thead><tr><th>Deskripsi</th><th>Qty</th><th class="text-end">Harga</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td class="text-end">@money($item->unit_price)</td>
                        <td class="text-end">@money($item->total_price)</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    @foreach($invoice->voucherUsages as $vu)
                    <tr><td colspan="3" class="text-end text-success">Voucher {{ $vu->voucher?->code }}</td><td class="text-end text-success">- @money($vu->discount_applied)</td></tr>
                    @endforeach
                    <tr><th colspan="3" class="text-end">TOTAL</th><th class="text-end">@money($invoice->grand_total)</th></tr>
                    <tr><td colspan="3" class="text-end">Dibayar</td><td class="text-end">@money($totalPaid)</td></tr>
                    <tr><td colspan="3" class="text-end">Sisa</td><td class="text-end fw-bold text-danger">@money($remaining)</td></tr>
                </tfoot>
            </table>

            @if($invoice->paymentRecords->isNotEmpty())
            <h6 class="mt-3">Riwayat Pembayaran</h6>
            <table class="table table-sm table-borderless small">
                @foreach($invoice->paymentRecords as $pr)
                <tr><td>{{ $pr->payment_date->format('d/m/Y H:i') }}</td><td>{{ $pr->paymentMethod?->payment ?? '-' }}</td><td class="text-end">@money($pr->amount)</td></tr>
                @endforeach
            </table>
            @endif

            @if($remaining > 0)
            <hr>
            <h6>Upload Bukti Pembayaran</h6>
            <form method="POST" action="{{ route('customer.upload-payment', $invoice) }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-2">
                    <div class="col-md-7"><input type="file" name="payment_proof" class="form-control" accept="image/*" required></div>
                    <div class="col-md-3"><input type="text" name="notes" class="form-control" placeholder="Catatan (opsional)"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
</body>
</html>
