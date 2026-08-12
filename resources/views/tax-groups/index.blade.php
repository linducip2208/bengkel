@extends('layouts.app')
@section('title', 'Grup Pajak')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-layer-group me-2"></i>Grup Pajak</h4>
    <a href="{{ route('tax-groups.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>#</th><th>Nama Grup</th><th>Tarif Pajak</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($taxGroups as $item)
                <tr>
                    <td>{{ $loop->iteration + $taxGroups->firstItem() - 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td>
                        @forelse($item->rates as $rate)
                            <span class="badge bg-light text-dark border">{{ $rate->name }} ({{ number_format($rate->rate, 2) }}%)</span>
                        @empty
                            <span class="text-muted small">-</span>
                        @endforelse
                    </td>
                    <td>{!! $item->is_active ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' !!}</td>
                    <td class="text-end">
                        <a href="{{ route('tax-groups.edit', $item) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('tax-groups.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus grup pajak ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $taxGroups->links() }}</div>
</div></div>
@endsection
