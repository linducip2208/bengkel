@extends('layouts.app')
@section('title', 'Edit Custom Field')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-puzzle-piece me-2"></i>Edit Custom Field</h4>
    <a href="{{ route('custom-fields.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('custom-fields.update', $field) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Modul <span class="text-danger">*</span></label>
                <select name="module" class="form-select" required>
                    @foreach($modules as $m)
                        <option value="{{ $m }}" {{ old('module', $field->module) === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Nama Field <span class="text-danger">*</span></label>
                <input type="text" name="field_name" class="form-control" value="{{ old('field_name', $field->field_name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type <span class="text-danger">*</span></label>
                <select name="field_type" class="form-select" required>
                    @foreach($types as $t)
                        <option value="{{ $t }}" {{ old('field_type', $field->field_type) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Opsi (untuk type=select)</label>
                <textarea name="options" class="form-control" rows="3">{{ old('options', is_array($field->options) ? implode("\n", $field->options) : '') }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Urutan</label>
                <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $field->sort_order) }}" min="0">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_required" id="is_required" value="1" {{ old('is_required', $field->is_required) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_required">Wajib diisi</label>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $field->is_active) ? 'checked' : '' }}>
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
