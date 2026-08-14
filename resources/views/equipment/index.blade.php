@extends('layouts.app')
@section('title', 'Peralatan Bengkel')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-toolbox me-2"></i>Peralatan Bengkel</h4>
    <a href="{{ route('equipment.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Alat</a>
</div>
<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" class="row g-2">
            <div class="col-md-5"><input type="text" name="search" class="form-control" placeholder="Cari nama/kode..." value="{{ request('search') }}"></div>
            <div class="col-md-4">
                <select name="category" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Status</th><th>Maintenance</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($equipment as $eq)
                <tr>
                    <td><small class="text-muted">{{ $eq->code ?? '-' }}</small></td>
                    <td><a href="{{ route('equipment.show', $eq) }}"><strong>{{ $eq->name }}</strong></a></td>
                    <td><span class="badge bg-light text-dark">{{ $eq->category }}</span></td>
                    <td>
                        @php $s = ['available'=>'Hijau','in_use'=>'Biru','maintenance'=>'Kuning','broken'=>'Merah']; $c = ['available'=>'success','in_use'=>'info','maintenance'=>'warning','broken'=>'danger']; $l = ['available'=>'Tersedia','in_use'=>'Dipakai','maintenance'=>'Maintenance','broken'=>'Rusak']; @endphp
                        <span class="badge bg-{{ $c[$eq->status] }}">{{ $l[$eq->status] }}</span>
                    </td>
                    <td>
                        @if($eq->is_due_maintenance)
                            <span class="text-danger fw-bold small">OVERDUE</span>
                        @elseif($eq->next_maintenance_date)
                            <small class="text-muted">{{ $eq->next_maintenance_date->format('d/m/Y') }}</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('equipment.edit', $eq) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('equipment.destroy', $eq) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada peralatan.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="d-flex justify-content-end mt-3">{{ $equipment->links() }}</div>
@endsection
