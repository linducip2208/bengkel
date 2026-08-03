@extends('layouts.app')
@section('title', 'Negara')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-globe me-2"></i>Negara</h4>
    <a href="{{ route('countries.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width: 360px;">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari...">
            <button class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Code</th><th>Nama</th><th>Phone Code</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($countries as $c)
                <tr>
                    <td><strong>{{ $c->code ?: '-' }}</strong></td>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->phone_code ? '+'.$c->phone_code : '-' }}</td>
                    <td class="text-end">
                        <a href="{{ route('countries.edit', $c) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('countries.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada negara.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $countries->links() }}</div>
</div></div>
@endsection
