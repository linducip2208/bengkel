@extends('layouts.app')
@section('title', 'Edit Bahan Bakar')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-gas-pump me-2"></i>Edit Bahan Bakar</h4>
    <a href="{{ route('fuel-types.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('fuel-types.update', $fuelType) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Jenis Bahan Bakar <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $fuelType->name) }}" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        <a href="{{ route('fuel-types.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div></div>
@endsection
