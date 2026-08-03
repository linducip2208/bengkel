@extends('layouts.app')
@section('title', $equipment->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-toolbox me-2"></i>{{ $equipment->name }}</h4>
    <a href="{{ route('equipment.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card mb-3"><div class="card-body">
            <table class="table table-sm mb-0">
                <tr><td class="text-muted">Kode</td><td>{{ $equipment->code ?? '-' }}</td></tr>
                <tr><td class="text-muted">Kategori</td><td>{{ $equipment->category }}</td></tr>
                <tr><td class="text-muted">Status</td><td><span class="badge bg-{{ $equipment->status === 'available' ? 'success' : ($equipment->status === 'in_use' ? 'info' : ($equipment->status === 'maintenance' ? 'warning' : 'danger')) }}">{{ ucfirst($equipment->status) }}</span></td></tr>
                <tr><td class="text-muted">Harga Beli</td><td>@money($equipment->purchase_price)</td></tr>
                <tr><td class="text-muted">Tanggal Beli</td><td>{{ $equipment->purchase_date?->format('d M Y') ?? '-' }}</td></tr>
                <tr><td class="text-muted">Next Maintenance</td><td class="{{ $equipment->is_due_maintenance ? 'text-danger fw-bold' : '' }}">{{ $equipment->next_maintenance_date?->format('d M Y') ?? '-' }}</td></tr>
            </table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header"><strong>Maintenance Log</strong></div><div class="card-body">
            @forelse($equipment->maintenanceLogs as $log)
            <div class="border-bottom pb-2 mb-2"><small class="text-muted">{{ $log->created_at->format('d/m/Y') }}</small><br>{{ $log->description }}</div>
            @empty
            <p class="text-muted">Belum ada catatan maintenance.</p>
            @endforelse
        </div></div>
    </div>
</div>
@endsection
