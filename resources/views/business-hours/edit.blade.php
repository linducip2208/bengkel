@extends('layouts.app')
@section('title', 'Edit Jam Operasional')
@section('content')
@php
    $days = [0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'];
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-clock me-2"></i>Edit Jam Operasional — {{ $businessHour->branch->name ?? '' }}</h4>
    <a href="{{ route('branches.show', $businessHour->branch_id) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('business-hours.update', $businessHour) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Hari <span class="text-danger">*</span></label>
                <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                    @foreach($days as $i => $d)
                        <option value="{{ $i }}" {{ old('day_of_week', $businessHour->day_of_week) == $i ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
                @error('day_of_week') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Buka <span class="text-danger">*</span></label>
                <input type="time" name="open_time" class="form-control @error('open_time') is-invalid @enderror"
                    value="{{ old('open_time', \Carbon\Carbon::parse($businessHour->open_time)->format('H:i')) }}" required>
                @error('open_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Tutup <span class="text-danger">*</span></label>
                <input type="time" name="close_time" class="form-control @error('close_time') is-invalid @enderror"
                    value="{{ old('close_time', \Carbon\Carbon::parse($businessHour->close_time)->format('H:i')) }}" required>
                @error('close_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_closed" id="is_closed" value="1" {{ old('is_closed', $businessHour->is_closed) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_closed">Hari ini Tutup (libur)</label>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        </div>
    </form>
</div></div>
@endsection
