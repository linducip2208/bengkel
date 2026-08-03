@extends('layouts.app')
@section('title', 'Cabang')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-building me-2"></i>Cabang</h4>
    <a href="{{ route('branches.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Cabang</a>
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
                        <th>Nama Cabang</th>
                        <th>Alamat</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($branches as $branch)
                    <tr>
                        <td>{{ $loop->iteration + $branches->firstItem() - 1 }}</td>
                        <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                        <td><a href="{{ route('branches.show', $branch) }}">{{ $branch->name }}</a></td>
                        <td>{{ Str::limit($branch->address, 40) ?: '-' }}</td>
                        <td>{{ $branch->phone ?: '-' }}</td>
                        <td>
                            @if($branch->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Non-Aktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus cabang ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-3 text-muted">Belum ada cabang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $branches->links() }}</div>
    </div>
</div>
@endsection
