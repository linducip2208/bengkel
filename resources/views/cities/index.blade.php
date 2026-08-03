@extends('layouts.app')
@section('title', 'Kota')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-city me-2"></i>Kota</h4>
    <a href="{{ route('cities.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="state_id" class="form-select">
                <option value="">Semua Provinsi</option>
                @foreach($states as $s)
                    <option value="{{ $s->id }}" {{ request('state_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari..."></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Nama</th><th>Provinsi</th><th>Negara</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($cities as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->state->name ?? '-' }}</td>
                    <td>{{ $c->state->country->name ?? '-' }}</td>
                    <td class="text-end">
                        <a href="{{ route('cities.edit', $c) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('cities.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada kota.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $cities->links() }}</div>
</div></div>
@endsection
