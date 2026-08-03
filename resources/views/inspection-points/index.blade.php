@extends('layouts.app')
@section('title', 'Inspection Point Library')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-list-check me-2"></i>Inspection Point Library</h4>
    <a href="{{ route('inspection-points.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="observation_type_id" class="form-select">
                <option value="">Semua Tipe</option>
                @foreach($observationTypes as $ot)
                    <option value="{{ $ot->id }}" {{ request('observation_type_id') == $ot->id ? 'selected' : '' }}>{{ $ot->observation_type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari point...">
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr><th>#</th><th>Tipe Observasi</th><th>Point</th><th>Kategori</th><th>Urutan</th><th>Aktif</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($points as $p)
                <tr>
                    <td>{{ $loop->iteration + $points->firstItem() - 1 }}</td>
                    <td>{{ $p->observationType->observation_type ?? '-' }}</td>
                    <td>{{ $p->point }}</td>
                    <td>{{ $p->category ?? '-' }}</td>
                    <td>{{ $p->sort_order }}</td>
                    <td>
                        @if($p->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('inspection-points.edit', $p) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('inspection-points.destroy', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-3 text-muted">Belum ada inspection point.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $points->links() }}</div>
</div></div>
@endsection
