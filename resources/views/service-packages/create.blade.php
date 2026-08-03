@extends('layouts.app')
@section('title', 'Tambah Paket')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Paket Service</h4>
    <a href="{{ route('service-packages.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('service-packages.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nama Paket *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Tune-up 40rb, Ganti Oli, Rem Besar..." required></div>
        <div class="col-md-3"><label class="form-label">Kategori</label><select name="repair_category_id" class="form-select"><option value="">-- Pilih --</option>@foreach($categories as $cat)<option value="{{ $cat->id }}" {{ old('repair_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->repair_category_name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Estimasi (jam)</label><input type="number" name="estimated_hours" class="form-control" value="{{ old('estimated_hours', 1) }}" step="0.5" min="0.5"></div>
        <div class="col-md-6"><label class="form-label">Harga Paket (Rp) *</label><input type="number" name="price" class="form-control" value="{{ old('price', 0) }}" min="0" required></div>
        <div class="col-md-6"><div class="form-check mt-4"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked><label class="form-check-label">Aktif</label></div></div>
        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea></div>
        <div class="col-12"><label class="form-label">Item (JSON)</label><textarea name="items" rows="4" class="form-control" placeholder='[{"description":"Oli Mesin 5L","price":250000,"type":"part"},{"description":"Jasa Tune-up","price":150000,"type":"service"}]'>{{ old('items') }}</textarea><small class="text-muted">Format JSON array: description, price, type (part/service). Optional.</small></div>
        <div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
    </div>
</form>
</div></div>
@endsection
