@extends('layouts.app')
@section('title', 'Recalls')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-exclamation-triangle me-2"></i>Recalls</h4>
    <a href="{{ route('recalls.create') }}" class="btn btn-danger">
        <i class="fas fa-plus me-1"></i>+ New Recall
    </a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Cari judul, deskripsi..."
                    value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-2">
            <select name="severity" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Severity</option>
                <option value="low" {{ request('severity') === 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ request('severity') === 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high" {{ request('severity') === 'high' ? 'selected' : '' }}>High</option>
                <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>Critical</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="is_active" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-outline-secondary w-100">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Judul</th>
                    <th>Produk</th>
                    <th>Brand Kendaraan</th>
                    <th>Issue Date</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recalls as $recall)
                <tr>
                    <td>{{ $loop->iteration + $recalls->firstItem() - 1 }}</td>
                    <td>
                        <span class="fw-semibold">{{ $recall->title }}</span>
                        <small class="d-block text-muted">{{ Str::limit($recall->description, 60) }}</small>
                    </td>
                    <td>{{ $recall->product->name ?? '-' }}</td>
                    <td>{{ $recall->vehicleBrand->vehicle_brand ?? '-' }}</td>
                    <td>{{ $recall->issue_date?->format('d M Y') }}</td>
                    <td>
                        @php
                            $sevColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'orange', 'critical' => 'danger'];
                        @endphp
                        <span class="badge bg-{{ $sevColors[$recall->severity] ?? 'secondary' }} rounded-pill px-3">
                            {{ ucfirst($recall->severity) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $recall->is_active ? 'success' : 'secondary' }} rounded-pill px-3">
                            {{ $recall->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('recalls.edit', $recall) }}" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('recalls.destroy', $recall) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus recall ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Belum ada data recall.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end">
        {{ $recalls->links() }}
    </div>
</div></div>
@endsection
