@extends('layouts.app')
@section('title', 'Paket Service Template')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-cubes me-2"></i>Paket Service Template</h4>
    <a href="{{ route('service-packages.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Paket</a>
</div>
<div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>Nama</th><th>Kategori</th><th class="text-end">Harga</th><th class="text-end">Estimasi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
    <tbody>
        @forelse($packages as $pkg)
        <tr>
            <td><strong>{{ $pkg->name }}</strong></td>
            <td>{{ $pkg->repairCategory->repair_category_name ?? '-' }}</td>
            <td class="text-end">@money($pkg->price)</td>
            <td class="text-end">{{ $pkg->estimated_hours ? $pkg->estimated_hours . ' jam' : '-' }}</td>
            <td><span class="badge bg-{{ $pkg->is_active ? 'success' : 'secondary' }}">{{ $pkg->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="text-end">
                <a href="{{ route('service-packages.edit', $pkg) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                <form action="{{ route('service-packages.destroy', $pkg) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada paket.</td></tr>
        @endforelse
    </tbody>
</table>
</div></div>
<div class="d-flex justify-content-end mt-3">{{ $packages->links() }}</div>
@endsection
