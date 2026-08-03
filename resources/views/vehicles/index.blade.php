@extends('layouts.app')

@section('title', 'Kendaraan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-car me-2"></i>Kendaraan</h4>
    <a href="{{ route('vehicles.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Tambah Kendaraan
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari no. plat, customer..."
                        value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="customer_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Pelanggan</option>
                    @foreach($customers as $cust)
                        <option value="{{ $cust->id }}" {{ request('customer_id') == $cust->id ? 'selected' : '' }}>
                            {{ $cust->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="vehicle_type_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua Tipe</option>
                    @foreach($vehicleTypes as $vt)
                        <option value="{{ $vt->id }}" {{ request('vehicle_type_id') == $vt->id ? 'selected' : '' }}>
                            {{ $vt->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>No. Plat</th>
                        <th>Pelanggan</th>
                        <th>Tipe / Merek</th>
                        <th>Tahun</th>
                        <th>KM</th>
                        <th>Bahan Bakar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    <tr>
                        <td>{{ $loop->iteration + $vehicles->firstItem() - 1 }}</td>
                        <td>
                            <a href="{{ route('vehicles.show', $vehicle) }}" class="text-decoration-none fw-semibold">
                                {{ $vehicle->license_plate }}
                            </a>
                        </td>
                        <td>{{ $vehicle->customer->name ?? '-' }}</td>
                        <td>{{ $vehicle->vehicleType->name ?? '-' }} / {{ $vehicle->vehicleBrand->name ?? '-' }}</td>
                        <td>{{ $vehicle->year ?? '-' }}</td>
                        <td>{{ $vehicle->odometer ? number_format($vehicle->odometer) . ' km' : '-' }}</td>
                        <td>{{ $vehicle->fuelType->name ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-info me-1" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('vehicles.destroy', $vehicle) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Hapus kendaraan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">Belum ada data kendaraan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            {{ $vehicles->links() }}
        </div>
    </div>
</div>
@endsection
