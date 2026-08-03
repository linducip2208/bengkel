@extends('layouts.app')
@section('title', 'Tambah Currency')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-money-bill me-2"></i>Tambah Currency</h4>
    <a href="{{ route('currencies.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('currencies.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Code (3 huruf) <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code') }}" maxlength="3" required placeholder="IDR">
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-5">
                <label class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Indonesia Rupiah">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-2">
                <label class="form-label">Simbol</label>
                <input type="text" name="symbol" class="form-control" value="{{ old('symbol') }}" placeholder="Rp">
            </div>
            <div class="col-md-2">
                <label class="form-label">Rate <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" name="exchange_rate" class="form-control" value="{{ old('exchange_rate', 1) }}" required>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_default">Jadikan Default</label>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        </div>
    </form>
</div></div>
@endsection
