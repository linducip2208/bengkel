@extends('layouts.app')
@section('title', 'Satuan Produk')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Satuan Produk</h4>
    <a href="{{ route('product-units.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>#</th><th>Satuan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($productUnits as $item)
                <tr>
                    <td>{{ $loop->iteration + $productUnits->firstItem() - 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="text-end">
                        <a href="{{ route('product-units.edit', $item) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('product-units.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
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
    <div class="d-flex justify-content-end">{{ $productUnits->links() }}</div>
</div></div>
@endsection
