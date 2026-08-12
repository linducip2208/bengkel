@extends('layouts.app')
@section('title', 'Variasi Produk')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-sitemap me-2"></i>Variasi Produk</h4>
    <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali ke Produk
    </a>
</div>

<div class="alert alert-light border">
    <strong>{{ $product->name }}</strong> — <code>{{ $product->code }}</code>
    <span class="text-muted ms-2">Harga default: @money($product->price)</span>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">Tambah Variasi</h6></div>
            <div class="card-body">
                <form method="POST" action="{{ route('products.variations.store', $product) }}">
                    @csrf
                    <div class="mb-2">
                        <label>Nama Variasi *</label>
                        <input type="text" name="name" class="form-control" placeholder="Size M, Warna Hitam..." required>
                    </div>
                    <div class="mb-2">
                        <label>SKU</label>
                        <input type="text" name="sku" class="form-control" placeholder="PRD-SKU-001">
                    </div>
                    <div class="mb-2">
                        <label>Harga</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="Kosongkan = ikuti harga default">
                    </div>
                    <div class="mb-2">
                        <label>Stok</label>
                        <input type="number" name="stock" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" checked>
                        <label class="form-check-label" for="isActive">Aktif</label>
                    </div>
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="card-title mb-0">Daftar Variasi ({{ $variations->count() }})</h6></div>
            <div class="card-body">
                @forelse($variations as $v)
                <div class="border rounded p-3 mb-2">
                    <form method="POST" action="{{ route('products.variations.update', [$product, $v]) }}">
                        @csrf @method('PUT')
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="small text-muted">Nama</label>
                                <input type="text" name="name" value="{{ $v->name }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted">SKU</label>
                                <input type="text" name="sku" value="{{ $v->sku }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted">Harga</label>
                                <input type="number" step="0.01" name="price" value="{{ $v->price }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="small text-muted">Stok</label>
                                <input type="number" name="stock" value="{{ $v->stock }}" class="form-control form-control-sm" min="0">
                            </div>
                            <div class="col-md-1">
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active-{{ $v->id }}" {{ $v->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="active-{{ $v->id }}">Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button class="btn btn-sm btn-primary" title="Simpan"><i class="fas fa-check"></i></button>
                            </div>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('products.variations.destroy', [$product, $v]) }}" class="mt-1 text-end"
                          onsubmit="return confirm('Hapus variasi ini?');">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Hapus</button>
                    </form>
                </div>
                @empty
                <p class="text-center text-muted py-4 mb-0">Belum ada variasi.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
