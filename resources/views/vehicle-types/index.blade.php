@extends('layouts.app')
@section('title', 'Tipe Kendaraan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-truck me-2"></i>Tipe Kendaraan</h4>
    <a href="{{ route('vehicle-types.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light"><tr><th>#</th><th>Nama Tipe</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                    @forelse($vehicleTypes as $item)
                    <tr>
                        <td>{{ $loop->iteration + $vehicleTypes->firstItem() - 1 }}</td>
                        <td>{{ $item->name }}</td>
                        <td class="text-end">
                            <a href="{{ route('vehicle-types.edit', $item) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('vehicle-types.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $vehicleTypes->links() }}</div>
    </div>
</div>
@endsection
