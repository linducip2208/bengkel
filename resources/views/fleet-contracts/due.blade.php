@extends('layouts.app')
@section('title', 'Kendaraan Jatuh Tempo Servis')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-clock me-2"></i>Kendaraan Jatuh Tempo Servis</h4>
    <a href="{{ route('fleet-contracts.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kontrak</th>
                        <th>Customer</th>
                        <th>No. Plat</th>
                        <th>Servis Terakhir</th>
                        <th>Jatuh Tempo</th>
                        <th>Terlambat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($due as $d)
                    <tr>
                        <td><a href="{{ route('fleet-contracts.show', $d['contract']) }}" class="fw-semibold">{{ $d['contract']->name }}</a></td>
                        <td>{{ $d['contract']->customer?->name ?? '-' }}</td>
                        <td><strong>{{ $d['vehicle']->number_plate ?? '-' }}</strong></td>
                        <td>{{ $d['last_service_date']?->format('d M Y') ?? 'Belum pernah' }}</td>
                        <td>{{ $d['due_date']?->format('d M Y') ?? '-' }}</td>
                        <td><span class="badge bg-danger">{{ $d['days_overdue'] !== null ? abs($d['days_overdue']) . ' hari' : '-' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada kendaraan yang jatuh tempo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
