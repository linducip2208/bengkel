@extends('layouts.app')
@section('title', 'Edit Cabang')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-building me-2"></i>Edit Cabang</h4>
    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('branches.update', $branch) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Nama Cabang <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $branch->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Kode <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code', $branch->code) }}" maxlength="20" required>
                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label">Perusahaan</label>
                <select name="company_id" class="form-select @error('company_id') is-invalid @enderror">
                    <option value="">-- Tanpa Perusahaan --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id', $branch->company_id) == $company->id ? 'selected' : '' }}>{{ $company->name }} ({{ $company->code }})</option>
                    @endforeach
                </select>
                @error('company_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label">Alamat</label>
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $branch->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Telepon</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $branch->phone) }}">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $branch->email) }}">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Cabang Aktif</label>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
            <a href="{{ route('branches.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
