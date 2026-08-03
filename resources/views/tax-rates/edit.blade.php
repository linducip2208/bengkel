@extends('layouts.app')
@section('title', 'Edit Tarif Pajak')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-percentage me-2"></i>Edit Tarif Pajak</h4>
    <a href="{{ route('tax-rates.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('tax-rates.update', $taxRate) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Pajak <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $taxRate->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Tarif (%) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="rate" class="form-control @error('rate') is-invalid @enderror" value="{{ old('rate', $taxRate->rate) }}" required>
                @error('rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
            <a href="{{ route('tax-rates.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
