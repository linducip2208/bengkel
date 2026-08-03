@extends('layouts.app')
@section('title', 'Tambah Observation Point')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Tambah Observation Point</h4>
    <a href="{{ route('observation-points.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('observation-points.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Tipe Observasi <span class="text-danger">*</span></label>
                <select name="observation_type_id" class="form-select @error('observation_type_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Tipe --</option>
                    @foreach($observationTypes as $ot)
                        <option value="{{ $ot->id }}" {{ old('observation_type_id') == $ot->id ? 'selected' : '' }}>{{ $ot->observation_type }}</option>
                    @endforeach
                </select>
                @error('observation_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-7">
                <label class="form-label">Point <span class="text-danger">*</span></label>
                <input type="text" name="observation_point" class="form-control @error('observation_point') is-invalid @enderror" value="{{ old('observation_point') }}" placeholder="Mis: Rem depan aus, Ban gundul" required>
                @error('observation_point') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        </div>
    </form>
</div></div>
@endsection
