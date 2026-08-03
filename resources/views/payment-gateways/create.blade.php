@extends('layouts.app')
@section('title', 'Tambah Payment Gateway')
@section('content')
<div class="d-flex justify-content-between mb-4"><h4><i class="bi bi-credit-card me-2"></i>Tambah Payment Gateway</h4>
    <a href="{{ route('payment-gateways.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

@if(!empty($presets))
<div class="card mb-3 border-warning"><div class="card-body">
    <h6 class="mb-2"><i class="bi bi-magic me-1"></i>Quick Setup — Pilih Template PG Indonesia</h6>
    <p class="text-muted small mb-2">Pilih dari {{ count($presets) }} preset PG di bawah untuk auto-fill form (base URL, callback path, format, supported methods, deskripsi). Tetap bisa edit setelahnya. <strong>API Key & Secret tetap harus diisi sendiri</strong> dari dashboard masing-masing PG.</p>
    <div class="row g-2">
        <div class="col-md-8">
            <select id="presetSelect" class="form-select">
                <option value="">— Pilih PG untuk auto-fill —</option>
                @foreach($presets as $slug => $preset)
                    <option value="{{ $slug }}" data-preset='{{ json_encode($preset) }}'>{{ $preset['label'] ?? $slug }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select id="presetEnv" class="form-select">
                <option value="sandbox">Mode: Sandbox (Testing)</option>
                <option value="production">Mode: Production (Live)</option>
            </select>
        </div>
    </div>
    <div id="presetHelp" class="mt-2 small"></div>
</div></div>
@endif

<form action="{{ route('payment-gateways.store') }}" method="POST">
@csrf
<div class="card mb-3"><div class="card-body">
    <h6>Informasi Dasar</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nama Gateway <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required placeholder="contoh: Midtrans Snap kami, Xendit Invoice, QRIS BCA">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6"><label class="form-label">Format API <span class="text-danger">*</span></label>
            <select name="api_format" class="form-select @error('api_format') is-invalid @enderror" required>
                <option value="">-- Pilih Format --</option>
                <option value="redirect" {{ old('api_format') === 'redirect' ? 'selected' : '' }}>Redirect (Midtrans Snap, Doku, Xendit, dst)</option>
                <option value="embed" {{ old('api_format') === 'embed' ? 'selected' : '' }}>Embed Widget (Snap Core API, dst)</option>
                <option value="qr" {{ old('api_format') === 'qr' ? 'selected' : '' }}>QR Code Dinamis (QRIS)</option>
                <option value="manual_transfer" {{ old('api_format') === 'manual_transfer' ? 'selected' : '' }}>Manual Transfer (no API)</option>
            </select>
            @error('api_format')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <small class="text-muted">Pilih sesuai jenis PG yang Anda pakai. Bukan per-vendor, tapi per-format.</small>
        </div>
        <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <h6>Kredensial</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Base URL Gateway</label><input type="url" name="base_url" class="form-control" value="{{ old('base_url') }}" placeholder="https://api.sandbox.midtrans.com"></div>
        <div class="col-md-6"><label class="form-label">Callback Path</label><input type="text" name="callback_path" class="form-control" value="{{ old('callback_path') }}" placeholder="/v2/charge atau /snap/v1/transactions"></div>
        <div class="col-md-6"><label class="form-label">Merchant ID</label><input type="text" name="merchant_id" class="form-control" value="{{ old('merchant_id') }}"></div>
        <div class="col-md-6"><label class="form-label">API Key</label><input type="text" name="api_key" class="form-control font-monospace" value="{{ old('api_key') }}" placeholder="Server Key dari PG"><small class="text-muted">Akan di-encrypt sebelum disimpan</small></div>
        <div class="col-md-6"><label class="form-label">Secret Key (jika diperlukan)</label><input type="text" name="secret_key" class="form-control font-monospace" value="{{ old('secret_key') }}"></div>
        <div class="col-md-6"><label class="form-label">Supported Methods (CSV)</label><input type="text" name="supported_methods" class="form-control" value="{{ old('supported_methods') }}" placeholder="bca,bni,bri,qris,gopay"></div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <h6>Konfigurasi Lanjutan (JSON)</h6>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Extra Headers</label>
            <textarea name="extra_headers" class="form-control font-monospace" rows="4" placeholder='{"X-Custom-Header": "value"}'>{{ old('extra_headers') }}</textarea>
            <small class="text-muted">Header tambahan untuk request ke gateway</small>
        </div>
        <div class="col-md-6"><label class="form-label">Extra Config (body request)</label>
            <textarea name="extra_config" class="form-control font-monospace" rows="4" placeholder='{"payment_type": "bank_transfer", "bank_transfer": {"bank": "bca"}}'>{{ old('extra_config') }}</textarea>
            <small class="text-muted">Field tambahan yang di-merge ke body request</small>
        </div>
    </div>
</div></div>

<div class="card mb-3"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sandbox_mode" id="sandbox" value="1" {{ old('sandbox_mode', '1') ? 'checked' : '' }}><label class="form-check-label" for="sandbox">Sandbox Mode (testing)</label></div></div>
        <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" id="active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}><label class="form-check-label" for="active">Aktif</label></div></div>
        <div class="col-md-4"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_default" id="default" value="1" {{ old('is_default') ? 'checked' : '' }}><label class="form-check-label" for="default">Jadikan Default</label></div></div>
    </div>
</div></div>

<button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-1"></i>Simpan Gateway</button>
<a href="{{ route('payment-gateways.index') }}" class="btn btn-secondary">Batal</a>
</form>

<script>
(function() {
    const presetSel = document.getElementById('presetSelect');
    const envSel = document.getElementById('presetEnv');
    const help = document.getElementById('presetHelp');
    if (!presetSel) return;

    function applyPreset() {
        const opt = presetSel.options[presetSel.selectedIndex];
        if (!opt || !opt.value) { help.innerHTML = ''; return; }

        let preset;
        try { preset = JSON.parse(opt.dataset.preset); }
        catch (e) { console.error(e); return; }

        const isProduction = envSel.value === 'production';
        const baseUrl = isProduction ? (preset.base_url_production || '') : (preset.base_url_sandbox || '');

        // Auto-fill text fields
        const setField = (name, value) => {
            const el = document.querySelector(`[name="${name}"]`);
            if (el && value !== undefined && value !== null) el.value = value;
        };

        setField('name', preset.name || '');
        setField('api_format', preset.api_format || '');
        setField('base_url', baseUrl);
        setField('callback_path', preset.callback_path || '');
        setField('supported_methods', preset.supported_methods || '');
        setField('description', preset.description || '');

        // Extra config example (kalau ada)
        if (preset.extra_config_example) {
            setField('extra_config', JSON.stringify(preset.extra_config_example, null, 2));
        }

        // Toggle sandbox mode sesuai env
        const sandboxToggle = document.querySelector('[name="sandbox_mode"]');
        if (sandboxToggle) sandboxToggle.checked = !isProduction;

        // Tampilkan help text
        let helpHtml = `<div class="alert alert-success py-2 mb-0">
            <strong><i class="bi bi-check-circle me-1"></i>Form auto-fill dari template "${preset.label}".</strong>
            Mode: <span class="badge bg-${isProduction ? 'danger' : 'warning text-dark'}">${envSel.options[envSel.selectedIndex].text}</span>
        </div>`;
        if (preset.credentials_help) {
            helpHtml += `<div class="alert alert-info py-2 mt-2 mb-0"><i class="bi bi-key me-1"></i><strong>Cara dapat kredensial:</strong> ${preset.credentials_help}</div>`;
        }
        help.innerHTML = helpHtml;
    }

    presetSel.addEventListener('change', applyPreset);
    envSel.addEventListener('change', applyPreset);
})();
</script>
@endsection
