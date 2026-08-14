@extends('layouts.app')
@section('title', 'Kontrak Fleet')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-file-signature me-2"></i>Kontrak Fleet</h4>
    <div>
        <a href="{{ route('fleet-contracts.due') }}" class="btn btn-warning me-1"><i class="fas fa-clock me-1"></i>Jadwal Jatuh Tempo</a>
        <a href="{{ route('fleet-contracts.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Buat Kontrak</a>
    </div>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari nama kontrak / customer..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-2">
            <select name="is_active" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama Kontrak</th>
                    <th>Customer</th>
                    <th>Jumlah Kendaraan</th>
                    <th>Interval</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($contracts as $contract)
                <tr>
                    <td>{{ $loop->iteration + $contracts->firstItem() - 1 }}</td>
                    <td>
                        <a href="{{ route('fleet-contracts.show', $contract) }}" class="fw-semibold">{{ $contract->name }}</a>
                    </td>
                    <td>{{ $contract->customer?->name ?? '-' }}</td>
                    <td><span class="badge bg-secondary">{{ $contract->vehicles->count() }}</span></td>
                    <td>
                        <small>{{ $contract->service_interval_days ? $contract->service_interval_days . ' hari' : '-' }}</small>
                        <small class="d-block text-muted">{{ $contract->service_interval_km ? $contract->service_interval_km . ' km' : '' }}</small>
                    </td>
                    <td>
                        <small>{{ $contract->start_date?->format('d M Y') ?? '-' }} — {{ $contract->end_date?->format('d M Y') ?? '-' }}</small>
                    </td>
                    <td>
                        <span class="badge bg-{{ $contract->is_active ? 'success' : 'secondary' }}">{{ $contract->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('fleet-contracts.show', $contract) }}" class="btn btn-sm btn-outline-primary me-1" title="Detail"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('fleet-contracts.edit', $contract) }}" class="btn btn-sm btn-outline-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('fleet-contracts.destroy', $contract) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kontrak ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada kontrak fleet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $contracts->links() }}</div>
</div></div>
@endsection
