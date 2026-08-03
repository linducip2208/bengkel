@extends('layouts.app')
@section('title', 'Tambah Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Tambah Supplier</h4>
    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('suppliers.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nama Supplier <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control form-control-sm @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Kontak Person</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control form-control-sm @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}">
                    @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control form-control-sm @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Telepon</label>
                    <input type="text" name="phone" id="phone" class="form-control form-control-sm @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label for="tax_id" class="form-label">NPWP</label>
                    <input type="text" name="tax_id" id="tax_id" class="form-control form-control-sm @error('tax_id') is-invalid @enderror" value="{{ old('tax_id') }}" maxlength="50">
                    @error('tax_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea name="address" id="address" rows="2" class="form-control form-control-sm @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-12">
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea name="notes" id="notes" rows="3" class="form-control form-control-sm @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Supplier
                </button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
