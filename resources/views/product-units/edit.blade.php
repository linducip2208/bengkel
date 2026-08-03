@extends('layouts.app')
@section('title', 'Edit Satuan Produk')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Edit Satuan Produk</h4>
    <a href="{{ route('product-units.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('product-units.update', $productUnit) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Nama Satuan <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $productUnit->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        <a href="{{ route('product-units.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div></div>
@endsection
