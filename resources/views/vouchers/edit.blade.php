@extends('layouts.app')
@section('title', 'Edit Voucher')
@section('content')
<div class="d-flex justify-content-between mb-4"><h4><i class="bi bi-ticket-perforated me-2"></i>Edit Voucher</h4><a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a></div>
<div class="card"><div class="card-body">
<form action="{{ route('vouchers.update', $voucher) }}" method="POST">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Code <span class="text-danger">*</span></label><input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $voucher->code) }}" required></div>
        <div class="col-md-8"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" value="{{ old('name', $voucher->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Tipe</label><select name="type" class="form-select" required>
            <option value="percent" {{ old('type', $voucher->type) === 'percent' ? 'selected' : '' }}>Persen (%)</option>
            <option value="fixed" {{ old('type', $voucher->type) === 'fixed' ? 'selected' : '' }}>Nominal (Rp)</option>
        </select></div>
        <div class="col-md-4"><label class="form-label">Nilai</label><input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', $voucher->value) }}" required></div>
        <div class="col-md-4"><label class="form-label">Max Diskon</label><input type="number" name="max_discount" class="form-control" value="{{ old('max_discount', $voucher->max_discount) }}"></div>
        <div class="col-md-4"><label class="form-label">Min Pembelian</label><input type="number" name="min_purchase" class="form-control" value="{{ old('min_purchase', $voucher->min_purchase) }}"></div>
        <div class="col-md-4"><label class="form-label">Limit Pemakaian</label><input type="number" name="usage_limit" class="form-control" value="{{ old('usage_limit', $voucher->usage_limit) }}"></div>
        <div class="col-md-4"><div class="form-check form-switch mt-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $voucher->is_active) ? 'checked' : '' }}><label class="form-check-label" for="is_active">Aktif</label></div></div>
        <div class="col-md-6"><label class="form-label">Berlaku Dari</label><input type="date" name="valid_from" class="form-control" value="{{ old('valid_from', $voucher->valid_from?->format('Y-m-d')) }}"></div>
        <div class="col-md-6"><label class="form-label">Berlaku Sampai</label><input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', $voucher->valid_until?->format('Y-m-d')) }}"></div>
        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2">{{ old('description', $voucher->description) }}</textarea></div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Perbarui</button>
</form>
</div></div>
@endsection
