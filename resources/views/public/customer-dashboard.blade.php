<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — {{ $customer->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>body{background:#f1f5f9;padding:2rem 0;}</style>
</head>
<body>
    <div class="container" style="max-width: 960px;">
        <div class="card mb-3"><div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Halo, {{ $customer->name }} 👋</h4>
                <small class="text-muted">{{ $customer->phone }} • Tier: <span class="badge bg-info">{{ ucfirst($customer->membership_tier) }}</span> • Poin: <strong class="text-warning">{{ number_format($customer->loyalty_points) }}</strong></small>
            </div>
            <div>
                <a href="{{ route('public.booking') }}" class="btn btn-success"><i class="bi bi-calendar-plus me-1"></i>Booking Baru</a>
                <form action="{{ route('customer.logout') }}" method="POST" class="d-inline">
                    @csrf <button class="btn btn-outline-secondary"><i class="bi bi-box-arrow-right"></i> Logout</button>
                </form>
            </div>
        </div></div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card"><div class="card-body">
                    <h6><i class="bi bi-file-earmark-text me-2"></i>10 Invoice Terbaru</h6>
                    <table class="table table-sm">
                        <thead><tr><th>No</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($invoices as $inv)
                            <tr>
                                <td><code>{{ $inv->invoice_number }}</code></td>
                                <td>{{ $inv->invoice_date->format('d/m/y') }}</td>
                                <td class="text-end">Rp {{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                                <td>
                                    @if($inv->payment_status == 2)<span class="badge bg-success">Lunas</span>
                                    @elseif($inv->payment_status == 1)<span class="badge bg-warning text-dark">Cicil</span>
                                    @else<span class="badge bg-danger">Belum</span>@endif
                                </td>
                            </tr>
                            @empty<tr><td colspan="4" class="text-center text-muted py-2">Belum ada invoice.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div></div>
            </div>
            <div class="col-md-6">
                <div class="card"><div class="card-body">
                    <h6><i class="bi bi-tools me-2"></i>10 Service Terbaru</h6>
                    <table class="table table-sm">
                        <thead><tr><th>No Job</th><th>Tanggal</th><th>Kendaraan</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($services as $s)
                            <tr>
                                <td><strong>{{ $s->job_no }}</strong></td>
                                <td>{{ $s->service_date->format('d/m/y') }}</td>
                                <td>{{ $s->vehicle->number_plate ?? '-' }}</td>
                                <td>
                                    @switch((int) $s->done_status)
                                        @case(0)<span class="badge bg-secondary">Open</span>@break
                                        @case(1)<span class="badge bg-warning text-dark">In Process</span>@break
                                        @case(2)<span class="badge bg-success">Selesai</span>@break
                                    @endswitch
                                </td>
                            </tr>
                            @empty<tr><td colspan="4" class="text-center text-muted py-2">Belum ada service.</td></tr>@endforelse
                        </tbody>
                    </table>
                </div></div>
            </div>
        </div>

        <div class="card mt-3"><div class="card-body">
            <h6><i class="bi bi-key me-2"></i>Ganti Password</h6>
            <form action="{{ route('customer.change-password') }}" method="POST" class="row g-2">
                @csrf
                <div class="col-md-9"><input type="password" name="new_password" class="form-control" placeholder="Password baru (min 6 karakter)" required minlength="6"></div>
                <div class="col-md-3"><button class="btn btn-warning w-100"><i class="bi bi-shield-check me-1"></i>Ganti</button></div>
            </form>
        </div></div>
    </div>
</body>
</html>
