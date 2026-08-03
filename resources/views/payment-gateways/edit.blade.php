@extends('layouts.app')
@section('title', 'Edit ' . $gateway->name)
@section('content')
<div class="d-flex justify-content-between mb-4"><h4><i class="bi bi-credit-card me-2"></i>Edit {{ $gateway->name }}</h4>
    <a href="{{ route('payment-gateways.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<form action="{{ route('payment-gateways.update', $gateway) }}" method="POST">
@csrf @method('PUT')
<div class="card mb-3"><div class="card-body">
    <h6>Informasi Dasar</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nama Gateway</label><input type="text" name="name" class="form-control" value="{{ old('name', $gateway->name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Format API</label>
            <select name="api_format" class="form-select" required>
                @foreach(['redirect' => 'Redirect', 'embed' => 'Embed Widget', 'qr' => 'QR Code', 'manual_transfer' => 'Manual Transfer'] as $val => $lbl)
                <option value="{{ $val }}" {{ old('api_format', $gateway->api_format) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2">{{ old('description', $gateway->description) }}</textarea></div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <h6>Kredensial</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Base URL</label><input type="url" name="base_url" class="form-control" value="{{ old('base_url', $gateway->base_url) }}"></div>
        <div class="col-md-6"><label class="form-label">Callback Path</label><input type="text" name="callback_path" class="form-control" value="{{ old('callback_path', $gateway->callback_path) }}"></div>
        <div class="col-md-6"><label class="form-label">Merchant ID</label><input type="text" name="merchant_id" class="form-control" value="{{ old('merchant_id', $gateway->merchant_id) }}"></div>
        <div class="col-md-6"><label class="form-label">API Key (kosongkan jika tidak ganti)</label><input type="text" name="api_key" class="form-control font-monospace" placeholder="{{ $gateway->api_key_encrypted ? '••••••• (sudah di-set, encrypted)' : 'Belum di-set' }}"></div>
        <div class="col-md-6"><label class="form-label">Secret Key (kosongkan jika tidak ganti)</label><input type="text" name="secret_key" class="form-control font-monospace" placeholder="{{ $gateway->secret_key_encrypted ? '••••••• (sudah di-set, encrypted)' : 'Belum di-set' }}"></div>
        <div class="col-md-6"><label class="form-label">Supported Methods (CSV)</label><input type="text" name="supported_methods" class="form-control" value="{{ old('supported_methods', is_array($gateway->supported_methods) ? implode(',', $gateway->supported_methods) : '') }}"></div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <h6>Konfigurasi Lanjutan (JSON)</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Extra Headers</label>
            <textarea name="extra_headers" class="form-control font-monospace" rows="4">{{ old('extra_headers', is_array($gateway->extra_headers) ? json_encode($gateway->extra_headers, JSON_PRETTY_PRINT) : '') }}</textarea>
        </div>
        <div class="col-md-6"><label class="form-label">Extra Config</label>
            <textarea name="extra_config" class="form-control font-monospace" rows="4">{{ old('extra_config', is_array($gateway->extra_config) ? json_encode($gateway->extra_config, JSON_PRETTY_PRINT) : '') }}</textarea>
        </div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sandbox_mode" id="sandbox" value="1" {{ old('sandbox_mode', $gateway->sandbox_mode) ? 'checked' : '' }}><label class="form-check-label" for="sandbox">Sandbox Mode</label></div></div>
        <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="active" value="1" {{ old('is_active', $gateway->is_active) ? 'checked' : '' }}><label class="form-check-label" for="active">Aktif</label></div></div>
        <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_default" id="default" value="1" {{ old('is_default', $gateway->is_default) ? 'checked' : '' }}><label class="form-check-label" for="default">Default</label></div></div>
    </div>
</div></div>

<button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-1"></i>Perbarui</button>
</form>
@endsection
