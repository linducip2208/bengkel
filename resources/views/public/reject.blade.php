<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tolak Estimasi — {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); min-height: 100vh; padding: 1.5rem; }
        .container { max-width: 560px; }
        .card { border-radius: 18px; overflow: hidden; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 3rem;"></i>
                    <h4 class="mt-2 mb-0">Tolak estimasi servis ini?</h4>
                    <code class="small">{{ $service->job_no }}</code>
                </div>

                @if(session('success'))
                <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>
                @endif

                <div class="table-responsive mb-4">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted" width="130">Pelanggan</td><td><strong>{{ $service->customer?->name ?? '-' }}</strong></td></tr>
                        <tr><td class="text-muted">Kendaraan</td><td>{{ $service->vehicle?->number_plate ?? '-' }} — {{ $service->vehicle?->vehicleBrand?->name ?? '' }} {{ $service->vehicle?->model_name ?? '' }}</td></tr>
                        <tr><td class="text-muted">Kategori</td><td>{{ $service->repairCategory?->repair_category_name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Keluhan</td><td>{{ $service->title ?: '-' }}</td></tr>
                        <tr><td class="text-muted">Estimasi Biaya</td><td class="fs-5"><strong>Rp {{ number_format($service->charge ?? 0, 0, ',', '.') }}</strong></td></tr>
                    </table>
                </div>

                @if(!$service->cancelled_at)
                <div class="d-grid gap-2">
                    <form method="POST" action="{{ route('public.approval.reject.store', $service->approval_token) }}">
                        @csrf
                        <button class="btn btn-danger btn-lg w-100" onclick="return confirm('Yakin menolak estimasi ini?')"><i class="bi bi-x-circle me-1"></i>Ya, Tolak Estimasi</button>
                    </form>
                    <a href="{{ route('public.approval.approve', $service->approval_token) }}" class="btn btn-outline-success">Kembali — Setujui</a>
                </div>
                @else
                <div class="alert alert-secondary text-center mb-0">
                    <i class="bi bi-info-circle me-1"></i>Estimasi ini telah ditolak pada {{ $service->cancelled_at?->format('d M Y H:i') }}.
                </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
