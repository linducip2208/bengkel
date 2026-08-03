@extends('layouts.app')
@section('title', 'Tambah Merek Kendaraan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-trademark me-2"></i>Tambah Merek Kendaraan</h4>
    <a href="{{ route('vehicle-brands.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('vehicle-brands.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Tipe Kendaraan <span class="text-danger">*</span></label>
                <select name="vehicle_type_id" class="form-select @error('vehicle_type_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Tipe --</option>
                    @foreach($vehicleTypes as $vt)
                        <option value="{{ $vt->id }}" {{ old('vehicle_type_id') == $vt->id ? 'selected' : '' }}>{{ $vt->name }}</option>
                    @endforeach
                </select>
                @error('vehicle_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Nama Merek <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
            <a href="{{ route('vehicle-brands.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
