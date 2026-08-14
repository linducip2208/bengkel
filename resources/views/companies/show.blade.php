@extends('layouts.app')
@section('title', $company->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="fas fa-city me-2"></i>{{ $company->name }}
            <span class="badge bg-secondary ms-2">{{ $company->code }}</span>
            @if($company->is_active)<span class="badge bg-success ms-1">Aktif</span>@else<span class="badge bg-secondary ms-1">Non-Aktif</span>@endif
        </h4>
        <small class="text-muted">{{ $company->address }} {{ $company->phone ? '· '.$company->phone : '' }} {{ $company->email ? '· '.$company->email : '' }}</small>
    </div>
    <div>
        <a href="{{ route('companies.edit', $company) }}" class="btn btn-warning"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="{{ route('companies.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="text-muted small">Total Cabang</div>
            <div class="fs-3 fw-bold">{{ $stats['total_branches'] }}</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-center"><div class="card-body">
            <div class="text-muted small">Cabang Aktif</div>
            <div class="fs-3 fw-bold text-success">{{ $stats['active_branches'] }}</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong><i class="fas fa-store-alt me-1"></i>Daftar Cabang</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Cabang</th>
                    <th>Telepon</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($company->branches as $branch)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                    <td><a href="{{ route('branches.show', $branch) }}">{{ $branch->name }}</a></td>
                    <td>{{ $branch->phone ?: '-' }}</td>
                    <td>
                        @if($branch->is_active)<span class="badge bg-success">Aktif</span>
                        @else<span class="badge bg-secondary">Non-Aktif</span>@endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada cabang di perusahaan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
