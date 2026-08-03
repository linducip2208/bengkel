@extends('layouts.app')
@section('title', 'Tambah Peralatan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Peralatan</h4>
    <a href="{{ route('equipment.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('equipment.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nama *</label><input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required></div>
            <div class="col-md-3"><label class="form-label">Kode</label><input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"></div>
            <div class="col-md-3">
                <label class="form-label">Kategori *</label>
                <select name="category" class="form-select" required>
                    @foreach($categories as $cat)<option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Tanggal Beli</label><input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date') }}"></div>
            <div class="col-md-3"><label class="form-label">Harga Beli (Rp)</label><input type="number" name="purchase_price" class="form-control" value="{{ old('purchase_price') }}" min="0"></div>
            <div class="col-md-3"><label class="form-label">Cabang</label><select name="branch_id" class="form-select"><option value="">-- Semua --</option>@foreach($branches as $br)<option value="{{ $br->id }}" {{ old('branch_id') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>@endforeach</select></div>
            <div class="col-md-3">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Tersedia</option>
                    <option value="in_use">Dipakai</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="broken">Rusak</option>
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Next Maintenance</label><input type="date" name="next_maintenance_date" class="form-control" value="{{ old('next_maintenance_date') }}"></div>
            <div class="col-md-3"><label class="form-label">Interval Maintenance (hari)</label><input type="number" name="maintenance_interval_days" class="form-control" value="{{ old('maintenance_interval_days', 90) }}" min="1"></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
        </div>
    </form>
</div></div>
@endsection
