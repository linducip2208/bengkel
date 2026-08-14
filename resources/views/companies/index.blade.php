@extends('layouts.app')
@section('title', 'Perusahaan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-city me-2"></i>Perusahaan</h4>
    <a href="{{ route('companies.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Perusahaan</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="input-group" style="max-width: 360px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama / kode...">
                <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Nama Perusahaan</th>
                        <th>Alamat</th>
                        <th>Telepon</th>
                        <th>Cabang</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                    <tr>
                        <td>{{ $loop->iteration + $companies->firstItem() - 1 }}</td>
                        <td><span class="badge bg-secondary">{{ $company->code }}</span></td>
                        <td><a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a></td>
                        <td>{{ Str::limit($company->address, 40) ?: '-' }}</td>
                        <td>{{ $company->phone ?: '-' }}</td>
                        <td><span class="badge bg-info text-dark">{{ $company->branches_count }}</span></td>
                        <td>
                            @if($company->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Non-Aktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus perusahaan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-3 text-muted">Belum ada perusahaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $companies->links() }}</div>
    </div>
</div>
@endsection
