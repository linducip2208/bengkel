@extends('layouts.app')
@section('title', 'Tambah Tipe Observasi')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Tambah Tipe Observasi</h4>
    <a href="{{ route('observation-types.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('observation-types.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nama Tipe <span class="text-danger">*</span></label>
            <input type="text" name="observation_type" class="form-control @error('observation_type') is-invalid @enderror" value="{{ old('observation_type') }}" required>
            @error('observation_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        <a href="{{ route('observation-types.index') }}" class="btn btn-secondary">Batal</a>
    </form>
</div></div>
@endsection
