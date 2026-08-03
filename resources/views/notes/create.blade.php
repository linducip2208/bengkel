@extends('layouts.app')
@section('title', 'Tambah Catatan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Tambah Catatan</h4>
    <a href="{{ route('notes.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('notes.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Target Tipe <span class="text-danger">*</span></label>
                <select name="notable_type" class="form-select @error('notable_type') is-invalid @enderror" required>
                    <option value="">-- Pilih Tipe --</option>
                    @foreach($notableTypes as $t)
                        <option value="{{ $t }}" {{ old('notable_type', $selectedType) === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
                @error('notable_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Target ID <span class="text-danger">*</span></label>
                <input type="number" name="notable_id" class="form-control @error('notable_id') is-invalid @enderror" value="{{ old('notable_id', $selectedId) }}" required>
                @error('notable_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label">Catatan <span class="text-danger">*</span></label>
                <textarea name="content" class="form-control @error('content') is-invalid @enderror" rows="5" required>{{ old('content') }}</textarea>
                @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        </div>
    </form>
</div></div>
@endsection
