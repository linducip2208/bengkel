@extends('layouts.app')
@section('title', 'Edit Kategori Checkout')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-sign-out-alt me-2"></i>Edit Kategori Checkout</h4>
    <a href="{{ route('checkout-categories.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('checkout-categories.update', $checkoutCategory) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
            <input type="text" name="category_name" class="form-control @error('category_name') is-invalid @enderror" value="{{ old('category_name', $checkoutCategory->category_name) }}" required>
            @error('category_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        <a href="{{ route('checkout-categories.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div></div>
@endsection
