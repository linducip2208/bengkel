@extends('layouts.app')
@section('title', 'Edit Kontrak Fleet')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-file-signature me-2"></i>Edit Kontrak Fleet</h4>
    <a href="{{ route('fleet-contracts.show', $fleetContract) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
<form action="{{ route('fleet-contracts.update', $fleetContract) }}" method="POST">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nama Kontrak <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $fleetContract->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Customer <span class="text-danger">*</span></label>
            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                <option value="">— Pilih customer —</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customer_id', $fleetContract->customer_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $fleetContract->start_date?->format('Y-m-d')) }}">
            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $fleetContract->end_date?->format('Y-m-d')) }}">
            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Interval Servis (hari)</label>
            <input type="number" min="1" name="service_interval_days" class="form-control @error('service_interval_days') is-invalid @enderror" value="{{ old('service_interval_days', $fleetContract->service_interval_days ?? 90) }}">
            @error('service_interval_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Interval Servis (km)</label>
            <input type="number" min="0" name="service_interval_km" class="form-control @error('service_interval_km') is-invalid @enderror" value="{{ old('service_interval_km', $fleetContract->service_interval_km ?? 5000) }}">
            @error('service_interval_km')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Kendaraan dalam Kontrak</label>
            <select name="vehicle_ids[]" multiple class="form-select @error('vehicle_ids') is-invalid @enderror" size="8">
                @foreach($vehicles as $v)
                <option value="{{ $v->id }}" {{ in_array($v->id, old('vehicle_ids', $selectedVehicleIds)) ? 'selected' : '' }}>
                    {{ $v->number_plate }} — {{ $v->customer?->name ?? 'Tanpa pemilik' }} ({{ $v->model_name ?? '-' }})
                </option>
                @endforeach
            </select>
            <small class="text-muted">Gunakan Ctrl+klik untuk memilih beberapa kendaraan.</small>
            @error('vehicle_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $fleetContract->notes) }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ old('is_active', $fleetContract->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="isActive">Kontrak Aktif</label>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Simpan Perubahan</button>
        <a href="{{ route('fleet-contracts.show', $fleetContract) }}" class="btn btn-outline-secondary ms-2">Batal</a>
    </div>
</form>
</div></div>
@endsection
