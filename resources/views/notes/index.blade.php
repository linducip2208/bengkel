@extends('layouts.app')
@section('title', 'Catatan Internal')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Catatan Internal</h4>
    <a href="{{ route('notes.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Catatan</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="notable_type" class="form-select">
                <option value="">Semua Target</option>
                @foreach($notableTypes as $t)
                    <option value="{{ $t }}" {{ request('notable_type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Waktu</th><th>Target</th><th>Catatan</th><th>Penulis</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($notes as $n)
                <tr>
                    <td>{{ $n->created_at->format('d M Y H:i') }}</td>
                    <td>
                        <small class="text-muted">{{ class_basename($n->notable_type) }} #{{ $n->notable_id }}</small>
                    </td>
                    <td>{{ Str::limit($n->content, 80) }}</td>
                    <td>{{ $n->author->name ?? 'Unknown' }}</td>
                    <td class="text-end">
                        <form action="{{ route('notes.destroy', $n) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada catatan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $notes->links() }}</div>
</div></div>
@endsection
