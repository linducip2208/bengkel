@extends('layouts.app')

@section('title', 'Pelanggan')

@section('content')
@if(session('import_errors'))
<div class="alert alert-warning small">
    <strong><i class="fas fa-exclamation-triangle me-1"></i>Beberapa baris gagal diimport:</strong>
    <ul class="mb-0 mt-1">
        @foreach(array_slice(session('import_errors'), 0, 10) as $err)
            <li>{{ $err }}</li>
        @endforeach
        @if(count(session('import_errors')) > 10)
            <li>... dan {{ count(session('import_errors')) - 10 }} error lainnya.</li>
        @endif
    </ul>
</div>
@endif
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Pelanggan</h4>
    <div>
        <a href="{{ route('customers.import-form') }}" class="btn btn-outline-secondary me-2">
            <i class="fas fa-upload me-1"></i>Import
        </a>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>+ New Customer
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, email, telepon..."
                        value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="per_page" class="form-select" onchange="this.form.submit()">
                    <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15 per halaman</option>
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 per halaman</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 per halaman</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Kota</th>
                        <th>Kendaraan</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>{{ $loop->iteration + $customers->firstItem() - 1 }}</td>
                        <td>
                            <a href="{{ route('customers.show', $customer) }}" class="text-decoration-none fw-semibold">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->city ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $customer->vehicles_count ?? $customer->vehicles()->count() }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-info me-1" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-warning me-1" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Hapus pelanggan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data pelanggan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end">
            {{ $customers->links() }}
        </div>
    </div>
</div>
@endsection
