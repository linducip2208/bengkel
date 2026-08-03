@extends('layouts.app')

@section('title', 'Daftar Servis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-wrench text-warning me-2"></i>Daftar Servis</h4>
    <a href="{{ route('services.create') }}" class="btn btn-danger">
        <i class="fas fa-plus me-1"></i> Servis Baru
    </a>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="stat-card card bg-white">
            <div class="card-body text-center">
                <h6>Total Open</h6>
                <h2 class="text-primary">{{ $stats['total_open'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card card bg-white">
            <div class="card-body text-center">
                <h6>In Progress</h6>
                <h2 class="text-warning">{{ $stats['in_progress'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card card bg-white">
            <div class="card-body text-center">
                <h6>Selesai Hari Ini</h6>
                <h2 class="text-success">{{ $stats['done_today'] }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="all">Semua Status</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>In Progress</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Done</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="Dari">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="Sampai">
            </div>
            <div class="col-md-2">
                <input type="text" name="customer_search" class="form-control form-control-sm" value="{{ request('customer_search') }}" placeholder="Cari pelanggan...">
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
                <a href="{{ route('services.index') }}" class="btn btn-sm btn-outline-danger w-100 mt-1">Reset</a>
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
                        <th>Tanggal</th>
                        <th class="text-center">Durasi</th>
                        <th>Status</th>
                        <th>Teknisi</th>
                        <th>Biaya</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr>
                        <td><span class="fw-semibold">{{ $service->job_no }}</span></td>
                        <td>{{ $service->customer->name ?? '-' }}</td>
                        <td>{{ $service->vehicle->number_plate ?? '-' }}</td>
                        <td>{{ $service->repairCategory->repair_category_name ?? '-' }}</td>
                        <td>{{ $service->service_date->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            <small class="{{ $service->is_overdue && !$service->completed_at ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $service->duration_label }}
                            </small>
                        </td>
                        <td>
                            @php
                                $statusLabels = [0 => 'Pending', 1 => 'In Progress', 2 => 'Done'];
                                $statusColors = [0 => 'secondary', 1 => 'warning', 2 => 'success'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$service->done_status] }} bg-opacity-10 text-{{ $statusColors[$service->done_status] }} rounded-pill px-3">
                                {{ $statusLabels[$service->done_status] }}
                            </span>
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
                            <a href="{{ route('services.edit', $service) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($service->done_status < 2)
                            <form action="{{ route('services.complete', $service) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" title="Selesai" onclick="return confirm('Tandai servis ini selesai?')">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @endif
                            <form action="{{ route('services.destroy', $service) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Hapus servis ini?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">Belum ada data servis.</td>
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
