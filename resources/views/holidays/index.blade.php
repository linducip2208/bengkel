@extends('layouts.app')
@section('title', 'Hari Libur')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-calendar-times me-2"></i>Hari Libur</h4>
    <a href="{{ route('holidays.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Libur</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="branch_id" class="form-select">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="year" value="{{ request('year') }}" class="form-control" placeholder="Tahun">
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>Tanggal</th><th>Nama Libur</th><th>Cabang</th><th>Berulang</th><th class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($holidays as $h)
                    <tr>
                        <td>{{ $h->date->format('d M Y') }} <small class="text-muted">({{ $h->date->locale('id')->translatedFormat('l') }})</small></td>
                        <td>{{ $h->name }}</td>
                        <td>{{ $h->branch->name ?? 'Semua Cabang' }}</td>
                        <td>
                            @if($h->is_recurring)<span class="badge bg-info">Tahunan</span>
                            @else<span class="badge bg-light text-dark">Sekali</span>@endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('holidays.edit', $h) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('holidays.destroy', $h) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus libur ini?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada hari libur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $holidays->links() }}</div>
    </div>
</div>
@endsection
