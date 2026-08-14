@extends('layouts.app')
@section('title', 'Daftar Produk')

@section('content')
@if(session('import_errors'))
<div class="alert alert-warning small">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Beberapa baris gagal diimport:</strong>
    <ul class="mb-0 mt-1">
        @foreach(array_slice(session('import_errors'), 0, 10) as $err)
            <li>{{ $err }}</li>
        @endforeach
        @if(count(session('import_errors')) > 10)
            <li>... dan {{ count(session('import_errors')) - 10 }} error lainnya.</li>
        @endif
    </ul>
</div>
@endif
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Produk</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('products.stock-opname') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-clipboard-check"></i> Stock Opname
        </a>
        <a href="{{ route('products.import-form') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-upload"></i> Import
        </a>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Tambah Produk
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('products.index') }}" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama/kode produk..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="product_type_id" class="form-select form-select-sm">
                    <option value="">Semua Tipe</option>
                    @foreach($productTypes as $type)
                        <option value="{{ $type->id }}" {{ request('product_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name ?? $type->type }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <input type="radio" class="btn-check" name="stock_status" id="stock-all" value="" {{ !request('stock_status') ? 'checked' : '' }}>
                    <label class="btn btn-outline-secondary" for="stock-all">Semua</label>

                    <input type="radio" class="btn-check" name="stock_status" id="stock-in" value="in_stock" {{ request('stock_status') == 'in_stock' ? 'checked' : '' }}>
                    <label class="btn btn-outline-success" for="stock-in">Stok Ada</label>

                    <input type="radio" class="btn-check" name="stock_status" id="stock-low" value="low" {{ request('stock_status') == 'low' ? 'checked' : '' }}>
                    <label class="btn btn-outline-warning" for="stock-low">Stok Rendah</label>

                    <input type="radio" class="btn-check" name="stock_status" id="stock-out" value="out" {{ request('stock_status') == 'out' ? 'checked' : '' }}>
                    <label class="btn btn-outline-danger" for="stock-out">Habis</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Satuan</th>
                    <th class="text-end">Harga</th>
                    <th class="text-end">Harga Beli</th>
                    <th class="text-center">Stok</th>
                    <th>Supplier</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td><small class="text-muted">{{ $product->code }}</small></td>
                    <td>
                        <a href="{{ route('products.show', $product) }}" class="text-decoration-none">
                            {{ $product->name }}
                        </a>
                    </td>
                    <td>{{ $product->productType?->name ?? $product->productType?->type ?? '-' }}</td>
                    <td>{{ $product->unit?->name ?? '-' }}</td>
                    <td class="text-end">@money($product->price)</td>
                    <td class="text-end">@money($product->cost_price ?? 0)</td>
                    <td class="text-center">
                        @if($product->current_stock <= 0)
                            <span class="badge bg-danger">0</span>
                        @elseif($product->minimum_stock && $product->current_stock <= $product->minimum_stock)
                            <span class="badge bg-warning text-dark">{{ $product->current_stock }}</span>
                        @else
                            <span class="badge bg-success">{{ $product->current_stock }}</span>
                        @endif
                        @if($product->reserved_quantity > 0)
                            <br><small class="text-muted" title="Stok direservasi">reservasi: {{ $product->reserved_quantity }}</small>
                        @endif
                    </td>
                    <td>{{ $product->supplier?->name ?? '-' }}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('products.barcode', $product) }}" class="btn btn-outline-secondary" title="Barcode" target="_blank">
                                <i class="bi bi-upc-scan"></i>
                            </a>
                            <a href="{{ route('products.stock-adjust', $product) }}" class="btn btn-outline-info" title="Stok Adjust">
                                <i class="bi bi-box-arrow-in-down"></i>
                            </a>
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">Tidak ada produk ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $products->total() }} produk</small>
        {{ $products->withQueryString()->links() }}
    </div>
</div>
@endsection
