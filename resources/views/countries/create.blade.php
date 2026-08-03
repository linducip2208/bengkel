@extends('layouts.app')
@section('title', 'Tambah Negara')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-globe me-2"></i>Tambah Negara</h4>
    <a href="{{ route('countries.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('countries.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Code (2 huruf)</label>
                <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code') }}" maxlength="2" placeholder="ID">
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Phone Code</label>
                <input type="text" name="phone_code" class="form-control" value="{{ old('phone_code') }}" placeholder="62">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        </div>
    </form>
</div></div>
@endsection
