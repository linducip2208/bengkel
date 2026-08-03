@extends('layouts.app')
@section('title', 'Tambah Jam Operasional')
@section('content')
@php
    $days = [0=>'Minggu',1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu'];
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-clock me-2"></i>Tambah Jam Operasional</h4>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('business-hours.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Cabang <span class="text-danger">*</span></label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Cabang --</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $selectedBranchId) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Hari <span class="text-danger">*</span></label>
                <select name="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                    @foreach($days as $i => $d)
                        <option value="{{ $i }}" {{ old('day_of_week') == $i ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
                @error('day_of_week') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Jam Buka <span class="text-danger">*</span></label>
                <input type="time" name="open_time" class="form-control @error('open_time') is-invalid @enderror" value="{{ old('open_time', '08:00') }}" required>
                @error('open_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Jam Tutup <span class="text-danger">*</span></label>
                <input type="time" name="close_time" class="form-control @error('close_time') is-invalid @enderror" value="{{ old('close_time', '17:00') }}" required>
                @error('close_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_closed" id="is_closed" value="1" {{ old('is_closed') ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_closed">Hari ini Tutup (libur)</label>
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
        </div>
    </form>
</div></div>
@endsection
