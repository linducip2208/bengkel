@extends('layouts.app')
@section('title', 'Tambah Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tambah Produk</h4>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('products.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">No. Produk</label>
                    <input type="text" class="form-control-plaintext" value="{{ $nextProductNo }} (auto)" readonly>
                </div>
                <div class="col-md-4">
                    <label for="code" class="form-label">Kode Produk <span class="text-danger">*</span></label>
                    <input type="text" name="code" id="code" class="form-control form-control-sm @error('code') is-invalid @enderror" value="{{ old('code') }}" required maxlength="50">
                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="product_type_id" class="form-label">Tipe Produk <span class="text-danger">*</span></label>
                    <select name="product_type_id" id="product_type_id" class="form-select form-select-sm @error('product_type_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Tipe --</option>
                        @foreach($productTypes as $type)
                            <option value="{{ $type->id }}" {{ old('product_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name ?? $type->type }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="unit_id" class="form-label">Satuan <span class="text-danger">*</span></label>
                    <select name="unit_id" id="unit_id" class="form-select form-select-sm @error('unit_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Satuan --</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror">
                        <option value="">-- Pilih Supplier (opsional) --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="price" class="form-label">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="price" id="price" class="form-control form-control-sm @error('price') is-invalid @enderror" value="{{ old('price') }}" required min="0" step="1" placeholder="0">
                    @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="cost_price" class="form-label">Harga Beli (Rp)</label>
                    <input type="number" name="cost_price" id="cost_price" class="form-control form-control-sm @error('cost_price') is-invalid @enderror" value="{{ old('cost_price') }}" min="0" step="1" placeholder="0">
                    @error('cost_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12">
                    <label for="warranty" class="form-label">Garansi</label>
                    <input type="text" name="warranty" id="warranty" class="form-control form-control-sm @error('warranty') is-invalid @enderror" value="{{ old('warranty') }}" maxlength="255" placeholder="Contoh: 6 bulan, 1 tahun">
                    @error('warranty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label for="initial_stock" class="form-label">Stok Awal</label>
                    <input type="number" name="initial_stock" id="initial_stock" class="form-control form-control-sm @error('initial_stock') is-invalid @enderror" value="{{ old('initial_stock', 0) }}" min="0" step="0.01">
                    @error('initial_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="minimum_stock" class="form-label">Stok Minimum</label>
                    <input type="number" name="minimum_stock" id="minimum_stock" class="form-control form-control-sm @error('minimum_stock') is-invalid @enderror" value="{{ old('minimum_stock') }}" min="0" step="0.01" placeholder="Peringatan jika stok di bawah ini">
                    @error('minimum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label for="rack_location" class="form-label">Lokasi Rak</label>
                    <input type="text" name="rack_location" id="rack_location" class="form-control form-control-sm @error('rack_location') is-invalid @enderror" value="{{ old('rack_location') }}" maxlength="100" placeholder="Contoh: Rak A-3">
                    @error('rack_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea name="description" id="description" rows="3" class="form-control form-control-sm @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Produk
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
