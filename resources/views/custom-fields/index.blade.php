@extends('layouts.app')
@section('title', 'Custom Fields')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>Custom Fields</h4>
    <a href="{{ route('custom-fields.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Field</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="module" class="form-select">
                <option value="">Semua Modul</option>
                @foreach($modules as $m)
                    <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>Modul</th><th>Field Name</th><th>Type</th><th>Required</th><th>Urutan</th><th>Aktif</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($fields as $f)
                <tr>
                    <td><span class="badge bg-info">{{ $f->module }}</span></td>
                    <td>{{ $f->field_name }}</td>
                    <td><code>{{ $f->field_type }}</code></td>
                    <td>@if($f->is_required)<span class="badge bg-warning text-dark">Wajib</span>@endif</td>
                    <td>{{ $f->sort_order }}</td>
                    <td>@if($f->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif</td>
                    <td class="text-end">
                        <a href="{{ route('custom-fields.edit', $f) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('custom-fields.destroy', $f) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-3 text-muted">Belum ada custom field.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $fields->links() }}</div>
</div></div>
@endsection
