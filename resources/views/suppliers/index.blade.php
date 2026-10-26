@extends('layouts.app')
@section('title', 'Daftar Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Supplier</h4>
    @can('supplier.create')
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Tambah Supplier
    </a>
    @endcan
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('suppliers.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/email/telepon..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>NPWP</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                <tr>
                    <td>{{ $loop->iteration + ($suppliers->currentPage() - 1) * $suppliers->perPage() }}</td>
                    <td>
                        @can('supplier.view')
                        <a href="{{ route('suppliers.show', $supplier) }}" class="text-decoration-none">
                            {{ $supplier->name }}
                        </a>
                        @else
                            {{ $supplier->name }}
                        @endcan
                    </td>
                    <td>{{ $supplier->contact_person ?: '-' }}</td>
                    <td>{{ $supplier->email ?: '-' }}</td>
                    <td>{{ $supplier->phone ?: '-' }}</td>
                    <td>{{ $supplier->tax_id ?: '-' }}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            @can('supplier.view')
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-outline-info" title="Detail" aria-label="Detail supplier">
                                <i class="bi bi-eye"></i>
                            </a>
                            @endcan
                            @can('supplier.edit')
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-warning" title="Edit" aria-label="Edit supplier">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                            @can('supplier.delete')
                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus supplier ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="fas fa-truck fa-2x mb-2 d-block opacity-50"></i>
                        Tidak ada supplier ditemukan.
                        @can('supplier.create')
                        <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-primary mt-2"><i class="fas fa-plus me-1"></i>Tambah Supplier</a>
                        @endcan
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $suppliers->total() }} supplier</small>
        {{ $suppliers->withQueryString()->links() }}
    </div>
</div>
@endsection
