@extends('layouts.app')
@section('title', 'Detail Kontrak Fleet')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-file-signature me-2"></i>Detail Kontrak Fleet</h4>
    <div>
        <a href="{{ route('fleet-contracts.edit', $fleetContract) }}" class="btn btn-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="{{ route('fleet-contracts.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Informasi Kontrak</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:150px;">Nama</td><td>{{ $fleetContract->name }}</td></tr>
                    <tr><td>Customer</td><td>{{ $fleetContract->customer?->name ?? '-' }}</td></tr>
                    <tr><td>Status</td><td><span class="badge bg-{{ $fleetContract->is_active ? 'success' : 'secondary' }}">{{ $fleetContract->is_active ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
                    <tr><td>Periode</td><td>{{ $fleetContract->start_date?->format('d M Y') ?? '-' }} — {{ $fleetContract->end_date?->format('d M Y') ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Interval Servis</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:150px;">Interval Hari</td><td>{{ $fleetContract->service_interval_days ? $fleetContract->service_interval_days . ' hari' : '-' }}</td></tr>
                    <tr><td>Interval Km</td><td>{{ $fleetContract->service_interval_km ? $fleetContract->service_interval_km . ' km' : '-' }}</td></tr>
                    <tr><td>Catatan</td><td>{{ $fleetContract->notes ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Kendaraan &amp; Status Jatuh Tempo</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Plat</th>
                        <th>Model</th>
                        <th>Pemilik</th>
                        <th>Servis Terakhir</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehiclesStatus as $vs)
                    <tr>
                        <td><strong>{{ $vs['vehicle']->number_plate ?? '-' }}</strong></td>
                        <td>{{ $vs['vehicle']->model_name ?? '-' }}</td>
                        <td>{{ $vs['vehicle']->customer?->name ?? '-' }}</td>
                        <td>{{ $vs['last_service_date']?->format('d M Y') ?? 'Belum pernah' }}</td>
                        <td>{{ $vs['due_date']?->format('d M Y') ?? '-' }}</td>
                        <td>
                            @if(!$vs['last_service_date'])
                                <span class="badge bg-info">Belum ada riwayat</span>
                            @elseif($vs['is_due'])
                                <span class="badge bg-danger">Jatuh tempo ({{ abs($vs['days_overdue']) }} hari lalu)</span>
                            @else
                                <span class="badge bg-success">{{ $vs['days_overdue'] }} hari lagi</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada kendaraan dalam kontrak ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
