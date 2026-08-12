@extends('layouts.app')

@section('title', 'Service History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-history text-primary me-2"></i>Service History</h4>
    <a href="{{ route('services.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali ke Servis Aktif
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="stat-card card bg-white">
            <div class="card-body text-center">
                <h6>Total Completed</h6>
                <h2 class="text-success">{{ $services->total() }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="Dari Tanggal">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="Sampai Tanggal">
            </div>
            <div class="col-md-2">
                <select name="customer_search" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Semua Pelanggan</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->name }}" {{ request('customer_search') == $c->name ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="vehicle_search" class="form-control form-control-sm" value="{{ request('vehicle_search') }}" placeholder="No. Plat">
            </div>
            <div class="col-md-2">
                <select name="technician" class="form-select form-select-sm">
                    <option value="">Semua Teknisi</option>
                    @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ request('technician') == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('services.history') }}" class="btn btn-sm btn-outline-danger w-100 mt-1">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Job No</th>
                        <th>Pelanggan</th>
                        <th>Kendaraan</th>
                        <th>Kategori</th>
                        <th>Tgl Servis</th>
                        <th>Tgl Selesai</th>
                        <th>Durasi</th>
                        <th>Teknisi</th>
                        <th>Biaya</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr>
                        <td><span class="fw-semibold">{{ $service->job_no }}</span></td>
                        <td>
                            <a href="{{ route('customers.show', $service->customer_id) }}" class="text-decoration-none">
                                {{ $service->customer->name ?? '-' }}
                            </a>
                        </td>
                        <td>
                            @if($service->vehicle)
                            <a href="{{ route('vehicles.history', $service->vehicle_id) }}" class="text-decoration-none">
                                {{ $service->vehicle->number_plate }}
                            </a>
                            <br><small class="text-muted">{{ $service->vehicle->model_name ?? '' }}</small>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $service->repairCategory->repair_category_name ?? '-' }}</td>
                        <td>{{ $service->service_date ? $service->service_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $service->completed_at ? $service->completed_at->format('d/m/Y H:i') : '-' }}</td>
                        <td>
                            <small class="text-muted">{{ $service->duration_label }}</small>
                        </td>
                        <td>
                            @foreach($service->technicians as $tech)
                                <span class="badge bg-light text-dark">{{ $tech->name }}</span>
                            @endforeach
                        </td>
                        <td>@include('partials.rupiah', ['amount' => $service->charge])</td>
                        <td class="text-end">
                            <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($service->invoice)
                            <a href="{{ route('invoices.pdf', $service->invoice) }}" class="btn btn-sm btn-outline-danger" title="Invoice PDF" target="_blank">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Belum ada riwayat servis selesai.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $services->links('partials.pagination') }}
        </div>
    </div>
</div>
@endsection
