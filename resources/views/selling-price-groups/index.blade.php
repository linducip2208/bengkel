@extends('layouts.app')
@section('title', 'Selling Price Groups')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-tags me-2"></i>Selling Price Groups</h4>
    <a href="{{ route('selling-price-groups.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i>Tambah Group
    </a>
</div>

<div class="table-responsive card">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Nama</th>
                <th>Deskripsi</th>
                <th class="text-center">Produk Dihargai</th>
                <th class="text-center">Customer Group</th>
                <th>Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $g)
            <tr>
                <td><strong>{{ $g->name }}</strong></td>
                <td class="text-muted">{{ $g->description ?: '-' }}</td>
                <td class="text-center">{{ $g->product_selling_prices_count }}</td>
                <td class="text-center">{{ $g->customer_groups_count }}</td>
                <td>
                    @if($g->is_active)
                    <span class="badge bg-success">Aktif</span>
                    @else
                    <span class="badge bg-secondary">Nonaktif</span>
                    @endif
                </td>
                <td class="text-end">
                    <a href="{{ route('selling-price-groups.prices', $g) }}" class="btn btn-sm btn-outline-primary" title="Atur Harga Produk">
                        <i class="fas fa-dollar-sign"></i> Harga
                    </a>
                    <a href="{{ route('selling-price-groups.edit', $g) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form method="POST" action="{{ route('selling-price-groups.destroy', $g) }}" class="d-inline"
                          onsubmit="return confirm('Hapus group harga ini?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada grup harga jual.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
