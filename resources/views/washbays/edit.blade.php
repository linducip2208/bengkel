@extends('layouts.app')
@section('title', 'Edit Washbay')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-shower me-2"></i>Edit Washbay</h4>
    <a href="{{ route('washbays.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('washbays.update', $washbay) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Washbay <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $washbay->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Cabang</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">Tanpa Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $washbay->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" id="statusSelect" required>
                    <option value="available" {{ old('status', $washbay->status) === 'available' ? 'selected' : '' }}>Kosong</option>
                    <option value="in_use" {{ old('status', $washbay->status) === 'in_use' ? 'selected' : '' }}>Dipakai</option>
                    <option value="maintenance" {{ old('status', $washbay->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6" id="serviceField">
                <label class="form-label">Service yang Dikerjakan</label>
                <select name="current_service_id" class="form-select @error('current_service_id') is-invalid @enderror">
                    <option value="">-- pilih service --</option>
                    @foreach($activeServices as $s)
                        <option value="{{ $s->id }}" {{ old('current_service_id', $washbay->current_service_id) == $s->id ? 'selected' : '' }}>
                            {{ $s->job_no }} — {{ $s->customer->name ?? '' }} ({{ $s->vehicle->number_plate ?? '' }})
                        </option>
                    @endforeach
                </select>
                @error('current_service_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        </div>
    </form>
</div></div>
<script>
    const statusSel = document.getElementById('statusSelect');
    const serviceField = document.getElementById('serviceField');
    function toggleField() {
        serviceField.style.display = statusSel.value === 'in_use' ? '' : 'none';
    }
    statusSel.addEventListener('change', toggleField);
    toggleField();
</script>
@endsection
