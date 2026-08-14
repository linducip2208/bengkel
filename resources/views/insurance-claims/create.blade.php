@extends('layouts.app')
@section('title', 'Buat Klaim Asuransi')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-file-invoice-dollar me-2"></i>Buat Klaim Asuransi Baru</h4>
    <a href="{{ route('insurance-claims.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
<form action="{{ route('insurance-claims.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Customer <span class="text-danger">*</span></label>
            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                <option value="">— Pilih customer —</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Service Terkait</label>
            <select name="service_id" class="form-select @error('service_id') is-invalid @enderror">
                <option value="">— Tidak terkait service —</option>
                @foreach($services as $s)
                <option value="{{ $s->id }}" {{ old('service_id') == $s->id ? 'selected' : '' }}>{{ $s->job_no }} — {{ $s->customer?->name ?? '?' }}</option>
                @endforeach
            </select>
            @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Kendaraan</label>
            <select name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror">
                <option value="">— Pilih kendaraan —</option>
                @foreach($customers as $c)
                    @foreach($c->vehicles as $v)
                    <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->number_plate }} ({{ $c->name }})</option>
                    @endforeach
                @endforeach
            </select>
            @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Perusahaan Asuransi</label>
            <input type="text" name="insurance_company" class="form-control @error('insurance_company') is-invalid @enderror" value="{{ old('insurance_company') }}" placeholder="Contoh: Allianz, AXA, Jasindo">
            @error('insurance_company')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Nomor Polis</label>
            <input type="text" name="policy_number" class="form-control @error('policy_number') is-invalid @enderror" value="{{ old('policy_number') }}">
            @error('policy_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal Klaim <span class="text-danger">*</span></label>
            <input type="date" name="claim_date" class="form-control @error('claim_date') is-invalid @enderror" value="{{ old('claim_date', date('Y-m-d')) }}" required>
            @error('claim_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Estimasi Nilai Klaim</label>
            <input type="number" step="0.01" min="0" name="estimated_amount" class="form-control @error('estimated_amount') is-invalid @enderror" value="{{ old('estimated_amount') }}">
            @error('estimated_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Catatan</label>
            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="4" placeholder="Detail kerusakan, kronologi, dokumen pendukung...">{{ old('notes') }}</textarea>
            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i>Simpan Klaim</button>
        <a href="{{ route('insurance-claims.index') }}" class="btn btn-outline-secondary ms-2">Batal</a>
    </div>
</form>
</div></div>
@endsection
