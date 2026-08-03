@extends('layouts.app')
@section('title', 'Subkontraktor')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-user-gear me-2"></i>Subkontraktor</h4>
    <a href="{{ route('subcontractors.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body p-0">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Nama</th><th>Telepon</th><th>Spesialisasi</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
            @forelse($subcontractors as $sc)
            <tr>
                <td><a href="{{ route('subcontractors.show', $sc) }}"><strong>{{ $sc->name }}</strong></a></td>
                <td>{{ $sc->phone ?? '-' }}</td>
                <td>{{ $sc->specialty ?? '-' }}</td>
                <td class="text-end">
                    <a href="{{ route('subcontractors.edit', $sc) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('subcontractors.destroy', $sc) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada subkontraktor.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
<div class="d-flex justify-content-end mt-3">{{ $subcontractors->links() }}</div>
@endsection
