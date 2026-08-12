<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service #{{ $service->job_no }} — Customer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>body{background:#f1f5f9;min-height:100vh;padding:1.5rem} .container{max-width:800px}</style>
</head>
<body>
<div class="container">
    <div class="mb-3"><a href="{{ route('customer.dashboard') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a></div>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Service #{{ $service->job_no }}</h5>
            <span class="badge bg-{{ $service->done_status >= 2 ? 'success' : ($service->done_status >= 1 ? 'primary' : 'warning') }}">
                {{ $service->done_status >= 2 ? 'Selesai' : ($service->done_status >= 1 ? 'Dalam Proses' : 'Pending') }}
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><small class="text-muted">Kendaraan</small><br><strong>{{ $service->vehicle?->model_name ?? '-' }}</strong><br><small>{{ $service->vehicle?->number_plate ?? '' }}</small></div>
                <div class="col-md-4"><small class="text-muted">Kategori</small><br><strong>{{ $service->repairCategory?->repair_category_name ?? '-' }}</strong></div>
                <div class="col-md-4"><small class="text-muted">Tanggal</small><br><strong>{{ $service->service_date->format('d M Y') }}</strong></div>
            </div>
            <hr>
            <h6>Deskripsi / Keluhan</h6>
            <p>{{ $service->description ?? '-' }}</p>

            @if($service->technicians->isNotEmpty())
            <h6>Teknisi</h6>
            <p>{{ $service->technicians->pluck('name')->implode(', ') }}</p>
            @endif

            @if($service->jobcardDetail)
            <h6>Detail Jobcard</h6>
            <table class="table table-sm table-borderless small">
                <tr><td style="width:140px;">Odometer Masuk</td><td>{{ number_format($service->jobcardDetail->odometer_in ?? 0, 0, ',', '.') }} km</td></tr>
                @if($service->jobcardDetail->odometer_out)<tr><td>Odometer Keluar</td><td>{{ number_format($service->jobcardDetail->odometer_out, 0, ',', '.') }} km</td></tr>@endif
                @if($service->jobcardDetail->findings)<tr><td>Temuan</td><td>{{ $service->jobcardDetail->findings }}</td></tr>@endif
                @if($service->jobcardDetail->recommendations)<tr><td>Rekomendasi</td><td>{{ $service->jobcardDetail->recommendations }}</td></tr>@endif
            </table>
            @endif

            @if($service->invoice)
            <hr>
            <h6>Invoice</h6>
            <table class="table table-sm">
                <thead><tr><th>Item</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    @foreach($service->invoice->items as $item)
                    <tr><td>{{ $item->description }}</td><td class="text-end">@money($item->total_price)</td></tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th class="text-end">Grand Total</th><th class="text-end">@money($service->invoice->grand_total)</th></tr></tfoot>
            </table>
            <a href="{{ route('customer.invoice', $service->invoice) }}" class="btn btn-primary btn-sm"><i class="bi bi-file-earmark-text"></i> Lihat Invoice</a>
            @endif
        </div>
    </div>
</div>
</body>
</html>
