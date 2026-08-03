@extends('layouts.app')
@section('title', 'Edit Inspection Point')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-list-check me-2"></i>Edit Inspection Point</h4>
    <a href="{{ route('inspection-points.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('inspection-points.update', $point) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Tipe Observasi</label>
                <select name="observation_type_id" class="form-select">
                    <option value="">-- Tanpa Tipe --</option>
                    @foreach($observationTypes as $ot)
                        <option value="{{ $ot->id }}" {{ old('observation_type_id', $point->observation_type_id) == $ot->id ? 'selected' : '' }}>{{ $ot->observation_type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Kategori</label>
                <input type="text" name="category" class="form-control" value="{{ old('category', $point->category) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $point->sort_order) }}" min="0">
            </div>
            <div class="col-12">
                <label class="form-label">Point <span class="text-danger">*</span></label>
                <input type="text" name="point" class="form-control @error('point') is-invalid @enderror" value="{{ old('point', $point->point) }}" required>
                @error('point') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $point->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Aktif</label>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        </div>
    </form>
</div></div>
@endsection
