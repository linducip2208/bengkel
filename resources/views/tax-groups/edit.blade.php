@extends('layouts.app')
@section('title', 'Edit Grup Pajak')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-layer-group me-2"></i>Edit Grup Pajak</h4>
    <a href="{{ route('tax-groups.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('tax-groups.update', $taxGroup) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Grup <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $taxGroup->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $taxGroup->description) }}">
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <label class="form-label">Tarif Pajak</label>
            <div class="border rounded p-3 @error('tax_rate_ids') border-danger @enderror">
                @php
                    $selectedRates = old('tax_rate_ids', $taxGroup->rates->pluck('id')->all());
                @endphp
                @forelse($taxRates as $rate)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tax_rate_ids[]" value="{{ $rate->id }}" id="rate-{{ $rate->id }}"
                        {{ in_array($rate->id, $selectedRates) ? 'checked' : '' }}>
                    <label class="form-check-label" for="rate-{{ $rate->id }}">
                        {{ $rate->name }} ({{ number_format($rate->rate, 2) }}%)
                    </label>
                </div>
                @empty
                <span class="text-muted">Belum ada tarif pajak. <a href="{{ route('tax-rates.create') }}">Buat tarif pajak</a> dulu.</span>
                @endforelse
            </div>
            @error('tax_rate_ids') <div class="text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="mt-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $taxGroup->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Aktif</label>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
            <a href="{{ route('tax-groups.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
