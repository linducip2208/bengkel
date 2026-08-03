@extends('layouts.app')

@section('title', 'Create Gate Pass - Bengkel Paten')

@section('content')
<h4 class="mb-3">Create Gate Pass</h4>

<div class="card">
    <div class="card-body">
        <form action="{{ route('gate-passes.store') }}" method="POST">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Vehicle <span class="text-danger">*</span></label>
                    <select name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                        <option value="">Select Vehicle</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                {{ $vehicle->number_plate }} - {{ $vehicle->customer->name ?? 'No Customer' }} ({{ $vehicle->model_name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                    @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Service (Optional)</label>
                    <select name="service_id" class="form-select @error('service_id') is-invalid @enderror">
                        <option value="">No Service</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->job_no }} - {{ $service->customer->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Entry Date/Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="entry_date" class="form-control @error('entry_date') is-invalid @enderror"
                           value="{{ old('entry_date', now()->format('Y-m-d\TH:i')) }}" required>
                    @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Driver Name</label>
                    <input type="text" name="driver_name" class="form-control" value="{{ old('driver_name') }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Driver Phone</label>
                    <input type="text" name="driver_phone" class="form-control" value="{{ old('driver_phone') }}">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Gate Pass</button>
            <a href="{{ route('gate-passes.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
