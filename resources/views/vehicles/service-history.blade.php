@extends('layouts.app')

@section('title', 'Riwayat Servis — ' . $vehicle->number_plate)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="fas fa-car me-2"></i>Riwayat Servis: {{ $vehicle->number_plate }}</h4>
        <small class="text-muted">
            {{ $vehicle->customer->name ?? 'Tanpa Pelanggan' }}
            @if($vehicle->vehicleBrand) &middot; {{ $vehicle->vehicleBrand->vehicle_brand }} @endif
            @if($vehicle->model_name) {{ $vehicle->model_name }} @endif
            &middot; {{ $vehicle->model_year ?? '-' }}
        </small>
    </div>
    <div>
        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Detail Kendaraan
        </a>
        @can('service.view')
        <a href="{{ route('services.create') }}?vehicle_id={{ $vehicle->id }}" class="btn btn-danger">
            <i class="fas fa-plus me-1"></i>+ New Job Card
        </a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="fas fa-tools fa-2x text-primary mb-2"></i>
                <h5 class="mb-0">{{ $services->total() }}</h5>
                <small class="text-muted">Total Servis</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h5 class="mb-0">{{ $vehicle->services()->where('done_status', 2)->count() }}</h5>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="fas fa-spinner fa-2x text-warning mb-2"></i>
                <h5 class="mb-0">{{ $vehicle->services()->where('done_status', '<', 2)->count() }}</h5>
                <small class="text-muted">Open / In Progress</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="fas fa-tachometer-alt fa-2x text-info mb-2"></i>
                <h5 class="mb-0">{{ $vehicle->odometer ? number_format($vehicle->odometer) . ' km' : '-' }}</h5>
                <small class="text-muted">Odometer Terakhir</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Servis</h5>
        <span class="badge bg-primary">{{ $services->total() }} servis</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Job No</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Durasi</th>
                        <th>Teknisi</th>
                        <th>Biaya</th>
                        <th>Invoice</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr>
                        <td><span class="fw-semibold">{{ $service->job_no }}</span></td>
                        <td>{{ $service->repairCategory->repair_category_name ?? '-' }}</td>
                        <td>{{ $service->service_date ? $service->service_date->format('d/m/Y H:i') : '-' }}</td>
                        <td>
                            @php
                                $statusLabels = [0 => 'Pending', 1 => 'In Progress', 2 => 'Done'];
                                $statusColors = [0 => 'secondary', 1 => 'warning', 2 => 'success'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$service->done_status] }} bg-opacity-10 text-{{ $statusColors[$service->done_status] }} rounded-pill px-3">
                                @if($service->workflow_status !== null)
                                    {{ $service->status_label }}
                                @else
                                    {{ $statusLabels[$service->done_status] }}
                                @endif
                            </span>
                            @if($service->completed_at)
                            <br><small class="text-muted">Selesai: {{ $service->completed_at->format('d/m/Y H:i') }}</small>
                            @endif
                        </td>
                        <td>
                            <small class="{{ $service->is_overdue && !$service->completed_at ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $service->duration_label }}
                            </small>
                        </td>
                        <td>
                            @foreach($service->technicians as $tech)
                                <span class="badge bg-light text-dark">{{ $tech->name }}</span>
                            @endforeach
                        </td>
                        <td>@include('partials.rupiah', ['amount' => $service->charge])</td>
                        <td>
                            @if($service->invoice)
                            <span class="badge bg-{{ $service->invoice->payment_status ? 'success' : 'warning' }}">
                                #{{ $service->invoice->invoice_number }}
                            </span>
                            @elseif($service->done_status == 2)
                            <span class="badge bg-secondary">—</span>
                            @else
                            <span class="badge bg-light text-muted">Belum</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($service->invoice)
                            <a href="{{ route('invoices.show', $service->invoice) }}" class="btn btn-sm btn-outline-info" title="Invoice">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            <a href="{{ route('invoices.pdf', $service->invoice) }}" class="btn btn-sm btn-outline-danger" title="PDF" target="_blank">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Belum ada riwayat servis untuk kendaraan ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($services->hasPages())
    <div class="card-footer">
        <div class="d-flex justify-content-center">
            {{ $services->links('partials.pagination') }}
        </div>
    </div>
    @endif
</div>
@endsection
