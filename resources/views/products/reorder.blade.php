@extends('layouts.app')
@section('title', 'Rekomendasi Reorder & Auto PO')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-boxes me-2"></i>Rekomendasi Reorder &amp; Auto PO</h4>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Daftar Produk
    </a>
</div>

<p class="text-muted small mb-3">
    Produk dengan stok di bawah / sama dengan stok minimum. Saran reorder = (min &times; 2) &minus; stok saat ini,
    dengan supplier termurah dipilih otomatis dari daftar harga supplier.
</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center">Min</th>
                    <th class="text-center">Saran Reorder</th>
                    <th>Supplier Termurah</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suggestions as $item)
                <tr>
                    <td>
                        <small class="text-muted">{{ $item['sku'] }}</small><br>
                        <strong>{{ $item['product_name'] }}</strong>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-{{ $item['current_stock'] <= 0 ? 'danger' : 'warning' }}">{{ $item['current_stock'] }}</span>
                    </td>
                    <td class="text-center">{{ $item['minimum_stock'] }}</td>
                    <td class="text-center">
                        <span class="badge bg-info">{{ $item['suggested_reorder'] }}</span>
                    </td>
                    <td>{{ $item['cheapest_supplier_name'] }}</td>
                    <td class="text-end">@money($item['cheapest_price'])</td>
                    <td class="text-center">
                        <form action="{{ route('products.reorder.po') }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Buat draft purchase order untuk produk ini?')">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $item['product_id'] }}">
                            <button class="btn btn-sm btn-primary" {{ $item['cheapest_supplier_id'] ? '' : 'disabled' }}>
                                <i class="bi bi-cart-plus"></i> Buat PO
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Tidak ada produk yang perlu reorder.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
