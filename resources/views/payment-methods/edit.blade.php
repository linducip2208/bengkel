@extends('layouts.app')
@section('title', 'Edit Metode Pembayaran')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Edit Metode Pembayaran</h4>
    <a href="{{ route('payment-methods.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('payment-methods.update', $paymentMethod) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Nama Metode <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $paymentMethod->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        <a href="{{ route('payment-methods.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div></div>
@endsection
