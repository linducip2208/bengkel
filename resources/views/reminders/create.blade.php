@extends('layouts.app')

@section('title', 'Create Reminder - Bengkel Paten')

@section('content')
<h4 class="mb-3">Create Reminder</h4>

<div class="card">
    <div class="card-body">
        <form action="{{ route('reminders.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }} ({{ $customer->phone }})</option>
                        @endforeach
                    </select>
                    @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                    <select name="vehicle_id" id="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                        <option value="">Select Customer First</option>
                    </select>
                    @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Reminder Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="service" {{ old('type') === 'service' ? 'selected' : '' }}>Service</option>
                        <option value="insurance" {{ old('type') === 'insurance' ? 'selected' : '' }}>Insurance</option>
                        <option value="stnk" {{ old('type') === 'stnk' ? 'selected' : '' }}>STNK</option>
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reminder Date <span class="text-danger">*</span></label>
                    <input type="date" name="reminder_date" class="form-control @error('reminder_date') is-invalid @enderror" value="{{ old('reminder_date', date('Y-m-d')) }}" required>
                    @error('reminder_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="3">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Reminder</button>
            <a href="{{ route('reminders.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('customer_id').addEventListener('change', function() {
    var customerId = this.value;
    var vehicleSelect = document.getElementById('vehicle_id');
    vehicleSelect.innerHTML = '<option value="">Loading...</option>';

    if (customerId) {
        fetch('/vehicles?customer_id=' + customerId, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            var options = '<option value="">Select Vehicle</option>';
            data.forEach(function(vehicle) {
                options += '<option value="' + vehicle.id + '">' + vehicle.number_plate + ' - ' + (vehicle.model_name || '') + '</option>';
            });
            vehicleSelect.innerHTML = options;
        })
        .catch(() => {
            vehicleSelect.innerHTML = '<option value="">Error loading vehicles</option>';
        });
    } else {
        vehicleSelect.innerHTML = '<option value="">Select Customer First</option>';
    }
});

@if(old('customer_id'))
document.getElementById('customer_id').dispatchEvent(new Event('change'));
@endif
</script>
@endpush
