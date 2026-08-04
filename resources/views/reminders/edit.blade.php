@extends('layouts.app')

@section('title', 'Edit Reminder - {{ config('app.name') }}')

@section('content')
<h4 class="mb-3">Edit Reminder</h4>

<div class="card">
    <div class="card-body">
        <form action="{{ route('reminders.update', $reminder) }}" method="POST">
            @csrf @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="customer_id" id="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $reminder->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                    <select name="vehicle_id" id="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $reminder->vehicle_id) == $vehicle->id ? 'selected' : '' }}>{{ $vehicle->number_plate }} - {{ $vehicle->model_name ?? '' }}</option>
                        @endforeach
                    </select>
                    @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Reminder Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="service" {{ old('type', $reminder->type) === 'service' ? 'selected' : '' }}>Service</option>
                        <option value="insurance" {{ old('type', $reminder->type) === 'insurance' ? 'selected' : '' }}>Insurance</option>
                        <option value="stnk" {{ old('type', $reminder->type) === 'stnk' ? 'selected' : '' }}>STNK</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Reminder Date <span class="text-danger">*</span></label>
                    <input type="date" name="reminder_date" class="form-control @error('reminder_date') is-invalid @enderror" value="{{ old('reminder_date', $reminder->reminder_date->format('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="3">{{ old('message', $reminder->message) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Reminder</button>
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
                var selected = vehicle.id == {{ $reminder->vehicle_id }} ? ' selected' : '';
                options += '<option value="' + vehicle.id + '"' + selected + '>' + vehicle.number_plate + ' - ' + (vehicle.model_name || '') + '</option>';
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
</script>
@endpush
