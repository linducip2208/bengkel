@extends('layouts.app')
@section('title', 'Tambah Voucher')
@section('content')
<div class="d-flex justify-content-between mb-4"><h4><i class="bi bi-ticket-perforated me-2"></i>Tambah Voucher</h4><a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a></div>
<div class="card"><div class="card-body">
<form action="{{ route('vouchers.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Code <span class="text-danger">*</span></label><input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" value="{{ old('code') }}" required placeholder="DISKON10">@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-8"><label class="form-label">Nama Promo <span class="text-danger">*</span></label><input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="Diskon 10% Service">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="col-md-4"><label class="form-label">Tipe <span class="text-danger">*</span></label><select name="type" class="form-select" required>
            <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Persen (%)</option>
            <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Nominal (Rp)</option>
        </select></div>
        <div class="col-md-4"><label class="form-label">Nilai <span class="text-danger">*</span></label><input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', 10) }}" required></div>
        <div class="col-md-4"><label class="form-label">Max Diskon (utk %)</label><input type="number" name="max_discount" class="form-control" value="{{ old('max_discount') }}" placeholder="Optional"></div>
        <div class="col-md-4"><label class="form-label">Min Pembelian</label><input type="number" name="min_purchase" class="form-control" value="{{ old('min_purchase', 0) }}"></div>
        <div class="col-md-4"><label class="form-label">Limit Pemakaian</label><input type="number" name="usage_limit" class="form-control" value="{{ old('usage_limit') }}" placeholder="Unlimited"></div>
        <div class="col-md-4"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', '1') ? 'checked' : '' }}><label class="form-check-label" for="is_active">Aktif</label></div></div>
        <div class="col-md-6"><label class="form-label">Berlaku Dari</label><input type="date" name="valid_from" class="form-control" value="{{ old('valid_from') }}"></div>
        <div class="col-md-6"><label class="form-label">Berlaku Sampai</label><input type="date" name="valid_until" class="form-control" value="{{ old('valid_until') }}"></div>
        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Simpan</button>
</form>
</div></div>
@endsection
