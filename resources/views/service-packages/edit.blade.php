@extends('layouts.app')
@section('title', 'Edit Paket')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Paket Service</h4>
    <a href="{{ route('service-packages.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('service-packages.update', $servicePackage) }}">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nama Paket *</label><input type="text" name="name" class="form-control" value="{{ old('name', $servicePackage->name) }}" required></div>
        <div class="col-md-3"><label class="form-label">Kategori</label><select name="repair_category_id" class="form-select"><option value="">-- Pilih --</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" {{ old('repair_category_id', $servicePackage->repair_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->repair_category_name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Estimasi (jam)</label><input type="number" name="estimated_hours" class="form-control" value="{{ old('estimated_hours', $servicePackage->estimated_hours) }}" step="0.5" min="0.5"></div>
        <div class="col-md-6"><label class="form-label">Harga Paket (Rp) *</label><input type="number" name="price" class="form-control" value="{{ old('price', $servicePackage->price) }}" min="0" required></div>
        <div class="col-md-6"><div class="form-check mt-4"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $servicePackage->is_active ? 'checked' : '' }}><label class="form-check-label">Aktif</label></div></div>
        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" rows="2" class="form-control">{{ old('description', $servicePackage->description) }}</textarea></div>
        <div class="col-12"><label class="form-label">Item (JSON)</label><textarea name="items" rows="4" class="form-control">{{ old('items', json_encode($servicePackage->items, JSON_PRETTY_PRINT)) }}</textarea><small class="text-muted">Format JSON array.</small></div>
        <div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
    </div>
</form>
</div></div>
@endsection
