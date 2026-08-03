@extends('layouts.app')
@section('title', 'Tambah Kota')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-city me-2"></i>Tambah Kota</h4>
    <a href="{{ route('cities.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('cities.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Provinsi <span class="text-danger">*</span></label>
                <select name="state_id" class="form-select @error('state_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Provinsi --</option>
                    @foreach($states as $s)
                        <option value="{{ $s->id }}" {{ old('state_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
                @error('state_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-7">
                <label class="form-label">Nama Kota <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        </div>
    </form>
</div></div>
@endsection
