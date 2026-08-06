@extends('layouts.app')

@section('title', 'Edit Kendaraan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-car-side me-2"></i>Edit Kendaraan: {{ $vehicle->license_plate }}</h4>
    <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('vehicles.update', $vehicle) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                    <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}" {{ old('customer_id', $vehicle->customer_id) == $cust->id ? 'selected' : '' }}>
                                {{ $cust->name }} ({{ $cust->phone }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nomor Plat</label>
                    <input type="text" name="number_plate" class="form-control @error('number_plate') is-invalid @enderror"
                        value="{{ old('number_plate', $vehicle->number_plate) }}">
                    @error('license_plate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipe Kendaraan <span class="text-danger">*</span></label>
                    <select name="vehicle_type_id" id="vehicle_type_id" class="form-select @error('vehicle_type_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Tipe --</option>
                        @foreach($vehicleTypes as $vt)
                            <option value="{{ $vt->id }}" {{ old('vehicle_type_id', $vehicle->vehicle_type_id) == $vt->id ? 'selected' : '' }}>
                                {{ $vt->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Merek</label>
                    <select name="vehicle_brand_id" id="vehicle_brand_id" class="form-select @error('vehicle_brand_id') is-invalid @enderror" onchange="document.getElementById('other_brand').style.display=this.value?'none':''">
                        <option value="">-- Pilih Merek --</option>
                        @foreach($vehicleBrands as $vb)
                            <option value="{{ $vb->id }}" data-type="{{ $vb->vehicle_type_id }}"
                                {{ old('vehicle_brand_id', $vehicle->vehicle_brand_id) == $vb->id ? 'selected' : '' }}>
                                {{ $vb->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="other_brand" id="other_brand" class="form-control form-control-sm mt-1" style="display:none" value="{{ old('other_brand') }}" placeholder="Atau ketik merek baru...">
                    @error('vehicle_brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Bahan Bakar <span class="text-danger">*</span></label>
                    <select name="fuel_type_id" class="form-select @error('fuel_type_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Bahan Bakar --</option>
                        @foreach($fuelTypes as $ft)
                            <option value="{{ $ft->id }}" {{ old('fuel_type_id', $vehicle->fuel_type_id) == $ft->id ? 'selected' : '' }}>
                                {{ $ft->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('fuel_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Model</label>
                    <input type="text" name="model_name" class="form-control @error('model_name') is-invalid @enderror"
                        value="{{ old('model_name', $vehicle->model_name) }}">
                    @error('model_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <input type="number" name="model_year" class="form-control @error('model_year') is-invalid @enderror"
                        value="{{ old('model_year', $vehicle->model_year) }}">
                    @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Warna</label>
                    <input type="text" name="color" class="form-control @error('color') is-invalid @enderror"
                        value="{{ old('color', $vehicle->color) }}">
                    @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">KM Saat Ini</label>
                    <input type="number" name="odometer" class="form-control @error('odometer') is-invalid @enderror"
                        value="{{ old('odometer', $vehicle->odometer) }}">
                    @error('odometer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">No. Rangka (VIN)</label>
                    <input type="text" name="chassis_number" class="form-control @error('chassis_number') is-invalid @enderror"
                        value="{{ old('chassis_number', $vehicle->chassis_number) }}">
                    @error('vin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">No. Mesin</label>
                    <input type="text" name="engine_number" class="form-control @error('engine_number') is-invalid @enderror"
                        value="{{ old('engine_number', $vehicle->engine_number) }}">
                    @error('engine_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $vehicle->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Perbarui
                    </button>
                    <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('vehicle_type_id').addEventListener('change', function() {
    const typeId = this.value;
    const brandSelect = document.getElementById('vehicle_brand_id');
    const options = brandSelect.querySelectorAll('option[data-type]');

    brandSelect.value = '';
    options.forEach(opt => {
        opt.style.display = (!typeId || opt.dataset.type === typeId) ? '' : 'none';
    });
});
</script>
@endpush
