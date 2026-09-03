@extends('layouts.app')

@section('title', 'Buat Estimasi Servis')

@php
    $canCreate = auth()->user()?->can('estimates.create');
    $oldItems = is_array(old('items')) ? old('items') : [];
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-file-signature text-warning me-2"></i>Buat Estimasi Servis</h4>
    <div>
        <a href="{{ route('estimates.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Daftar Estimasi</a>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger py-2"><i class="fas fa-triangle-exclamation me-1"></i>{{ session('error') }}</div>
@endif

{{-- ================================================== STEP 1: CHOOSE WO ================================================== --}}
@if($service === null)
<div class="card">
    <div class="card-body">
        <h6 class="mb-3"><i class="fas fa-search me-2 text-warning"></i>Pilih Service / Work Order</h6>
        <p class="small text-muted">Cari berdasarkan no service, nama/telepon pelanggan, plat kendaraan, atau judul pekerjaan.</p>
        <input type="text" id="woSearch" class="form-control mb-3" placeholder="Cari work order..." autocomplete="off" autofocus>
        <div id="woResults" class="list-group">
            <div class="text-muted text-center py-4">Memuat work order terbaru...</div>
        </div>
    </div>
</div>
@endif

{{-- ================================================== STEP 2: BUILD FORM ================================================== --}}
@if($service !== null && $lockedEstimate ?? null)
{{-- Issued/approved/converted estimate → show state + actions, no builder. --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="fw-bold fs-5">{{ $service->job_no }}</div>
                <div class="small text-muted">
                    {{ $service->customer?->name ?? '-' }} · {{ $service->vehicle?->number_plate ?? '-' }} ·
                    <span class="badge bg-light text-dark">{{ \App\Models\Service::WORKFLOW_LABELS[$service->workflow_status] ?? $service->workflow_status }}</span>
                </div>
            </div>
            <a href="{{ route('estimates.create') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-repeat me-1"></i> Ganti WO</a>
        </div>
    </div>
</div>
<div class="card border-warning">
    <div class="card-body text-center py-4">
        <i class="fas fa-file-signature fa-3x text-warning mb-3"></i>
        <h5>Service ini sudah memiliki estimasi terbit</h5>
        <p class="text-muted mb-3">
            {{ $lockedEstimate->estimate_number }} v{{ $lockedEstimate->version }} —
            <span class="badge bg-{{ $lockedEstimate->statusColor() }}">{{ $lockedEstimate->statusLabel() }}</span>
        </p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="{{ route('estimates.preview', $lockedEstimate) }}" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-eye me-1"></i> Lihat Estimasi</a>
            @if($lockedEstimate->status === \App\Models\ServiceEstimate::STATUS_CONVERTED && $lockedEstimate->invoice)
                <a href="{{ route('invoices.show', $lockedEstimate->invoice) }}" class="btn btn-outline-primary"><i class="fas fa-file-invoice me-1"></i> Lihat Invoice</a>
            @elseif(auth()->user()?->can('estimates.revise'))
                <a href="{{ route('services.show', $service) }}#tab-estimate" class="btn btn-warning"><i class="fas fa-code-branch me-1"></i> Buat Revisi</a>
            @endif
            <a href="{{ route('services.show', $service).'#tab-estimate' }}" class="btn btn-outline-secondary"><i class="fas fa-toolbox me-1"></i> Buka Service</a>
        </div>
    </div>
</div>

@elseif($service !== null)
{{-- ================================================== EXISTING DRAFT banner ================================================== --}}
@if($continuingDraft !== null)
<div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <strong><i class="fas fa-file-pen me-1"></i> Lanjutkan Draft</strong> —
        estimasi <strong>{{ $continuingDraft->estimate_number }}</strong> (v{{ $continuingDraft->version }}) sudah ada untuk service ini.
        Simpan akan memperbarui draft yang sama, bukan membuat baru.
    </div>
    <a href="{{ route('estimates.preview', $continuingDraft) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-eye me-1"></i> Lihat Draft
    </a>
</div>
@endif

{{-- Service not yet inspected notice (allow SA to continue) --}}
@if((int) $service->workflow_status < 2)
<div class="alert alert-info py-2">
    <i class="fas fa-circle-info me-1"></i> Service belum diperiksa. Anda tetap dapat membuat estimasi, tetapi pastikan hasil pemeriksaan sudah dilakukan agar temuan lengkap.
</div>
@endif
<form method="POST" action="{{ route('services.estimates.store', $service) }}" id="estimateBuildForm">
    @csrf

    {{-- WO header --}}
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="fw-bold fs-5">{{ $service->job_no }}</div>
                    <div class="small text-muted d-flex flex-wrap gap-3">
                        <span><i class="fas fa-user me-1"></i> {{ $service->customer?->name ?? '-' }} <span class="badge bg-light text-dark">otomatis</span></span>
                        <span><i class="fas fa-car me-1"></i> {{ trim(($service->vehicle?->vehicleBrand?->vehicle_brand ?? '').' '.($service->vehicle?->model_name ?? '')) ?: '-' }} · <strong>{{ $service->vehicle?->number_plate ?? '-' }}</strong> <span class="badge bg-light text-dark">otomatis</span></span>
                        @if($service->repairCategory)<span><i class="fas fa-tag me-1"></i> {{ $service->repairCategory->repair_category_name }}</span>@endif
                        <span><i class="fas fa-timeline me-1"></i> <span class="badge bg-light text-dark">{{ \App\Models\Service::WORKFLOW_LABELS[$service->workflow_status] ?? $service->workflow_status }}</span></span>
                    </div>
                </div>
                <input type="hidden" name="redirect_to" value="estimates">
                <a href="{{ route('estimates.create') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-repeat me-1"></i> Ganti WO</a>
            </div>
        </div>
    </div>

    {{-- TEMUAN (reference) --}}
    @if($findings->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header"><strong><i class="fas fa-magnifying-glass me-2 text-warning"></i>TEMUAN</strong> <small class="text-muted">— referensi, bukan harga</small></div>
        <div class="card-body py-2">
            @foreach($findings as $finding)
            <div class="d-flex justify-content-between align-items-center border-bottom py-1 small">
                <span>
                    <span class="badge {{ $finding->severity === 'critical' ? 'bg-danger' : ($finding->severity === 'repair_required' ? 'bg-warning text-dark' : 'bg-warning bg-opacity-50 text-dark') }}">{{ \App\Models\ServiceFinding::SEVERITY_BADGES[$finding->severity] ?? '' }} {{ $finding->finding_number }}</span>
                    <strong class="ms-1">{{ $finding->title }}</strong>
                    @if($finding->measurement_value !== null)<small class="text-muted ms-1">{{ $finding->measurement_value }}{{ $finding->measurement_unit ? ' '.$finding->measurement_unit : '' }}</small>@endif
                </span>
                <span class="badge bg-light text-dark">{{ \App\Models\ServiceFinding::STATUS_LABELS[$finding->status] ?? $finding->status }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- WORK PACKAGE --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong><i class="fas fa-briefcase me-2 text-warning"></i>WORK PACKAGE</strong>
            <a href="{{ route('services.show', $service) }}#tab-work" class="btn btn-sm btn-outline-warning">
                <i class="fas fa-plus me-1"></i> Kelola / Buat Rencana Pekerjaan
            </a>
        </div>
        <div class="card-body py-2">
            <p class="small text-muted mb-2">Centang pekerjaan yang masuk estimasi — customer nanti menyetujui per paket.</p>
            @forelse($packages as $package)
            @php $totals = $package->computeTotals(); @endphp
            <label class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 bg-white" style="cursor:pointer">
                <span>
                    <input type="checkbox" name="packages[]" value="{{ $package->id }}" class="wp-check me-1" checked>
                    <strong>{{ $package->title }}</strong>
                    @if($package->finding)<span class="badge {{ $package->finding->severity === 'critical' ? 'bg-danger' : 'bg-warning text-dark' }} ms-1">dari {{ $package->finding->finding_number }}</span>@else<span class="badge bg-secondary ms-1">manual</span>@endif
                    <small class="text-muted d-block ms-4">Std: {{ $totals['standard_minutes'] }} mnt · Jasa Rp {{ number_format($totals['labor_total'], 0, ',', '.') }} · Part Rp {{ number_format($totals['part_total'], 0, ',', '.') }}</small>
                </span>
                <strong>Rp {{ number_format($totals['grand_total'], 0, ',', '.') }}</strong>
            </label>
            @empty
            <div class="text-muted small py-2">
                Belum ada work package. <a href="{{ route('services.show', $service) }}#tab-work">Buat dari tab Pekerjaan</a> atau tambahkan item manual di bawah.
            </div>
            @endforelse
        </div>
    </div>

    {{-- MANUAL ITEMS --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <strong><i class="fas fa-list me-2 text-warning"></i>ITEM MANUAL</strong>
            <div class="d-flex gap-1 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow('labor')"><i class="fas fa-tools me-1"></i>+ Tambah Jasa</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow('part')"><i class="fas fa-cog me-1"></i>+ Tambah Part</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItemRow('other')"><i class="fas fa-plus me-1"></i>+ Item Manual</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:110px">Tipe</th>
                            <th>Deskripsi</th>
                            <th style="min-width:80px">Qty</th>
                            <th style="min-width:110px">Harga</th>
                            <th style="min-width:80px">Diskon</th>
                            <th class="text-center" style="min-width:60px">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="manualItems">
                        @forelse($oldItems as $i => $row)
                        <tr class="manual-item-row">
                            <td>
                                <select name="items[{{ $i }}][item_type]" class="form-select form-select-sm">
                                    <option value="labor" @selected(($row['item_type'] ?? '') === 'labor')>Jasa</option>
                                    <option value="part" @selected(($row['item_type'] ?? '') === 'part')>Part</option>
                                    <option value="other" @selected(($row['item_type'] ?? 'other') === 'other')>Manual</option>
                                </select>
                            </td>
                            <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $row['description'] ?? '' }}"></td>
                            <td><input type="number" step="0.001" min="0" name="items[{{ $i }}][quantity]" class="form-control form-control-sm qty-input" value="{{ $row['quantity'] ?? 1 }}"></td>
                            <td><input type="number" step="1" min="0" name="items[{{ $i }}][unit_price]" class="form-control form-control-sm price-input" value="{{ $row['unit_price'] ?? 0 }}"></td>
                            <td><input type="number" step="1" min="0" name="items[{{ $i }}][discount]" class="form-control form-control-sm discount-input" value="{{ $row['discount'] ?? 0 }}"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                        </tr>
                        @empty
                        <tr class="manual-item-row">
                            <td>
                                <select name="items[0][item_type]" class="form-select form-select-sm">
                                    <option value="labor">Jasa</option>
                                    <option value="part">Part</option>
                                    <option value="other" selected>Manual</option>
                                </select>
                            </td>
                            <td><input type="text" name="items[0][description]" class="form-control form-control-sm" placeholder="Deskripsi"></td>
                            <td><input type="number" step="0.001" min="0" name="items[0][quantity]" class="form-control form-control-sm qty-input" value="1"></td>
                            <td><input type="number" step="1" min="0" name="items[0][unit_price]" class="form-control form-control-sm price-input" value="0"></td>
                            <td><input type="number" step="1" min="0" name="items[0][discount]" class="form-control form-control-sm discount-input" value="0"></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row g-2 mb-2">
                <div class="col-6 col-md-3">
                    <label class="form-label small">Tanggal Estimasi</label>
                    <input type="date" name="estimate_date" class="form-control form-control-sm" value="{{ old('estimate_date', now()->toDateString()) }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small">Berlaku Sampai</label>
                    <input type="date" name="valid_until" class="form-control form-control-sm" value="{{ old('valid_until', now()->addDays(7)->toDateString()) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label small">Catatan</label>
                    <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes') }}" placeholder="Keluhan / catatan pemeriksaan">
                </div>
            </div>

            {{-- LIVE TOTAL --}}
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <table class="table table-sm mb-0" id="liveTotals">
                        <tr><td class="text-muted">Total Item Manual</td><td class="text-end" id="manualTotal">Rp 0</td></tr>
                        <tr><td class="text-muted">Total Work Package</td><td class="text-end" id="packageTotal">Rp {{ number_format($packages->sum(fn ($p) => $p->computeTotals()['grand_total']), 0, ',', '.') }}</td></tr>
                        <tr class="table-dark"><td class="fw-bold">TOTAL</td><td class="text-end fw-bold" id="grandTotal">Rp 0</td></tr>
                    </table>
                    <small class="text-muted d-block mt-1"><i class="fas fa-shield-alt me-1"></i>Total dihitung ulang server saat disimpan. Estimasi tidak mengurangi stok.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('services.show', $service) }}#tab-estimate" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-warning btn-lg">
            <i class="fas fa-save me-1"></i> Simpan Draft
        </button>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var searchUrl = '{{ route('estimates.service-search') }}';
    var input = document.getElementById('woSearch');
    var results = document.getElementById('woResults');
    var timer = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function actionButton(s) {
        var badge = '';
        var btn = '';
        if (s.action === 'continue_draft') {
            badge = '<span class="badge bg-warning text-dark ms-1">Draft ' + esc(s.estimate.number) + '</span>';
            btn = '<span class="btn btn-sm btn-warning">Lanjutkan Draft</span>';
        } else if (s.action === 'view') {
            badge = '<span class="badge bg-info ms-1">' + esc(s.estimate.status_label) + '</span>';
            btn = '<span class="btn btn-sm btn-outline-secondary">Lihat Estimasi</span>';
        } else if (s.action === 'revise') {
            badge = '<span class="badge bg-success ms-1">' + esc(s.estimate.status_label) + '</span>';
            btn = '<span class="btn btn-sm btn-outline-warning">Buat Revisi</span>';
        } else {
            btn = '<span class="btn btn-sm btn-warning">Buat Estimasi</span>';
        }
        return badge + btn;
    }

    function render(list) {
        if (! list.length) {
            results.innerHTML = '<div class="text-muted text-center py-4">'
                + 'Tidak ada work order yang cocok. Service yang sudah selesai (Invoice/Paid/Completed) tidak muncul — buat Service baru untuk pekerjaan berikutnya.'
                + '</div>';
            return;
        }
        results.innerHTML = list.map(function (s) {
            var note = s.needs_inspection
                ? '<small class="text-warning d-block"><i class="fas fa-circle-info me-1"></i>Service belum diperiksa.</small>'
                : '';
            var badge = s.has_active_estimate
                ? '<span class="badge bg-info ms-1">' + esc(s.estimate.status_label) + ' ' + esc(s.estimate.number) + '</span>'
                : '';
            return '<a href="' + esc(s.url) + '" class="list-group-item list-group-item-action">'
                + '<div class="d-flex justify-content-between align-items-center">'
                + '<div>'
                + '<strong>' + esc(s.job_no) + '</strong>' + badge
                + '<div class="small">' + esc(s.customer || '-') + (s.phone ? ' · ' + esc(s.phone) : '') + '</div>'
                + '<small class="text-muted">' + esc(s.model || '-') + ' · <strong>' + esc(s.plate || '-') + '</strong></small>'
                + note
                + '</div>'
                + '<div class="text-end">'
                + '<div class="small text-muted mb-1">' + esc(s.workflow_label) + '</div>'
                + actionButton(s)
                + '</div>'
                + '</div>'
                + '</a>';
        }).join('');
    }

    function search(q) {
        fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { render(data.results || []); })
            .catch(function () { results.innerHTML = '<div class="text-danger text-center py-3">Gagal memuat. Coba lagi.</div>'; });
    }

    if (input) {
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { search(input.value.trim()); }, 250);
        });
        search(''); // initial load
    }

    // ============ builder page: live totals + dynamic rows ============
    var form = document.getElementById('estimateBuildForm');
    if (! form) { return; }

    function rp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }

    function rowTotal(row) {
        var qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        var price = parseFloat(row.querySelector('.price-input').value) || 0;
        var disc = parseFloat(row.querySelector('.discount-input').value) || 0;
        return Math.max(0, qty * price - disc);
    }

    function refreshTotals() {
        var manual = 0;
        document.querySelectorAll('.manual-item-row').forEach(function (row) {
            manual += rowTotal(row);
        });
        var pkg = 0;
        document.querySelectorAll('.wp-check:checked').forEach(function (cb) {
            pkg += parseFloat(cb.getAttribute('data-total') || '0');
        });
        var grand = manual + pkg;
        document.getElementById('manualTotal').textContent = rp(manual);
        document.getElementById('packageTotal').textContent = rp(pkg);
        document.getElementById('grandTotal').textContent = rp(grand);
    }

    // Stamp package totals onto checkboxes for JS math.
    document.querySelectorAll('.wp-check').forEach(function (cb) {
        var label = cb.closest('label');
        var m = label ? label.querySelector('strong + strong') : null;
        var totalText = label ? (label.textContent.match(/Rp ([\d.]+)/g) || []).pop() : null;
        var num = totalText ? parseFloat(totalText.replace(/[Rp.\s]/g, '')) : 0;
        cb.setAttribute('data-total', isNaN(num) ? '0' : String(num));
        cb.addEventListener('change', refreshTotals);
    });

    document.addEventListener('input', function (e) {
        if (e.target.closest('.manual-item-row')) { refreshTotals(); }
    });
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-row');
        if (btn) {
            var rows = document.querySelectorAll('.manual-item-row');
            if (rows.length > 1) {
                btn.closest('.manual-item-row').remove();
            } else {
                // keep one empty row
                var row = btn.closest('.manual-item-row');
                row.querySelectorAll('input[type=text], input[type=number]').forEach(function (i) { i.value = i.classList.contains('qty-input') ? '1' : '0'; });
            }
            refreshTotals();
        }
    });

    var rowIndex = {{ count($oldItems) > 0 ? count($oldItems) : 1 }};
    window.addItemRow = function (type) {
        var tbody = document.getElementById('manualItems');
        var tr = document.createElement('tr');
        tr.className = 'manual-item-row';
        tr.innerHTML = ''
            + '<td><select name="items[' + rowIndex + '][item_type]" class="form-select form-select-sm">'
            + '<option value="labor"' + (type === 'labor' ? ' selected' : '') + '>Jasa</option>'
            + '<option value="part"' + (type === 'part' ? ' selected' : '') + '>Part</option>'
            + '<option value="other"' + (type === 'other' ? ' selected' : '') + '>Manual</option>'
            + '</select></td>'
            + '<td><input type="text" name="items[' + rowIndex + '][description]" class="form-control form-control-sm" placeholder="Deskripsi"></td>'
            + '<td><input type="number" step="0.001" min="0" name="items[' + rowIndex + '][quantity]" class="form-control form-control-sm qty-input" value="1"></td>'
            + '<td><input type="number" step="1" min="0" name="items[' + rowIndex + '][unit_price]" class="form-control form-control-sm price-input" value="0"></td>'
            + '<td><input type="number" step="1" min="0" name="items[' + rowIndex + '][discount]" class="form-control form-control-sm discount-input" value="0"></td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>';
        tbody.appendChild(tr);
        rowIndex++;
        tr.querySelector('input[type=text]').focus();
        refreshTotals();
    };

    refreshTotals();

    // Never submit a fully empty estimate.
    form.addEventListener('submit', function (e) {
        var hasPackage = document.querySelectorAll('.wp-check:checked').length > 0;
        var hasItem = Array.from(document.querySelectorAll('.manual-item-row')).some(function (row) {
            var desc = row.querySelector('input[type=text]');
            return desc && desc.value.trim() !== '';
        });
        if (! hasPackage && ! hasItem) {
            e.preventDefault();
            alert('Estimasi harus memiliki minimal satu work package tercentang atau item manual dengan deskripsi.');
        }
    });
})();
</script>
@endpush
