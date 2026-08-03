@extends('layouts.app')
@section('title', 'Edit Peralatan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Peralatan</h4>
    <a href="{{ route('equipment.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('equipment.update', $equipment) }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nama *</label><input type="text" name="name" class="form-control" value="{{ old('name', $equipment->name) }}" required></div>
            <div class="col-md-3"><label class="form-label">Kode</label><input type="text" name="code" class="form-control" value="{{ old('code', $equipment->code) }}"></div>
            <div class="col-md-3"><label class="form-label">Kategori *</label>
                <select name="category" class="form-select" required>
                    @foreach($categories as $cat)<option value="{{ $cat }}" {{ old('category', $equipment->category) == $cat ? 'selected' : '' }}>{{ $cat }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Tanggal Beli</label><input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $equipment->purchase_date?->format('Y-m-d')) }}"></div>
            <div class="col-md-3"><label class="form-label">Harga Beli (Rp)</label><input type="number" name="purchase_price" class="form-control" value="{{ old('purchase_price', $equipment->purchase_price) }}" min="0"></div>
            <div class="col-md-3"><label class="form-label">Cabang</label><select name="branch_id" class="form-select"><option value="">-- Semua --</option>@foreach($branches as $br)<option value="{{ $br->id }}" {{ old('branch_id', $equipment->branch_id) == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    @foreach(['available'=>'Tersedia','in_use'=>'Dipakai','maintenance'=>'Maintenance','broken'=>'Rusak'] as $v=>$l)
                    <option value="{{ $v }}" {{ old('status', $equipment->status) == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><label class="form-label">Next Maintenance</label><input type="date" name="next_maintenance_date" class="form-control" value="{{ old('next_maintenance_date', $equipment->next_maintenance_date?->format('Y-m-d')) }}"></div>
            <div class="col-md-3"><label class="form-label">Interval Maintenance (hari)</label><input type="number" name="maintenance_interval_days" class="form-control" value="{{ old('maintenance_interval_days', $equipment->maintenance_interval_days) }}" min="1"></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $equipment->notes) }}</textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
        </div>
    </form>
</div></div>
@endsection
