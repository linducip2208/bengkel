@extends('layouts.app')

@section('title', 'Estimasi Servis')

@php
    $canCreate = auth()->user()?->can('estimates.create');
    $canSend = auth()->user()?->can('estimates.send');
    $canRevise = auth()->user()?->can('estimates.revise');
    $canConvert = auth()->user()?->can('estimates.convert_invoice');

    // Status chips shown above the table (clickable filters).
    $statusChips = [
        'all' => ['label' => 'Semua', 'color' => 'primary', 'count' => $counts['all'] ?? 0],
        \App\Models\ServiceEstimate::STATUS_DRAFT => ['label' => 'Draft', 'color' => 'secondary', 'count' => $counts[\App\Models\ServiceEstimate::STATUS_DRAFT] ?? 0],
        \App\Models\ServiceEstimate::STATUS_WAITING_APPROVAL => ['label' => 'Menunggu Persetujuan', 'color' => 'warning', 'count' => $counts[\App\Models\ServiceEstimate::STATUS_WAITING_APPROVAL] ?? 0],
        \App\Models\ServiceEstimate::STATUS_APPROVED => ['label' => 'Disetujui', 'color' => 'success', 'count' => $counts[\App\Models\ServiceEstimate::STATUS_APPROVED] ?? 0],
        \App\Models\ServiceEstimate::STATUS_REJECTED => ['label' => 'Ditolak', 'color' => 'danger', 'count' => $counts[\App\Models\ServiceEstimate::STATUS_REJECTED] ?? 0],
        \App\Models\ServiceEstimate::STATUS_EXPIRED => ['label' => 'Kedaluwarsa', 'color' => 'dark', 'count' => $counts[\App\Models\ServiceEstimate::STATUS_EXPIRED] ?? 0],
        \App\Models\ServiceEstimate::STATUS_CONVERTED => ['label' => 'Sudah Jadi Invoice', 'color' => 'primary', 'count' => $counts[\App\Models\ServiceEstimate::STATUS_CONVERTED] ?? 0],
    ];
    $activeStatus = $filters['status'] !== '' ? $filters['status'] : 'all';
@endphp

@section('content')
{{-- Post-create success: keep user in the estimate workflow with quick actions --}}
@if(session('success') && session('created_estimate_id'))
@php $createdEstimate = \App\Models\ServiceEstimate::withoutGlobalScopes()->find(session('created_estimate_id')); @endphp
@if($createdEstimate)
<div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><i class="fas fa-check-circle me-1"></i> Estimasi <strong>{{ $createdEstimate->estimate_number }}</strong> berhasil dibuat.</div>
    <div class="d-flex gap-2">
        <a href="{{ route('estimates.preview', $createdEstimate) }}" target="_blank" class="btn btn-sm btn-outline-success"><i class="fas fa-eye me-1"></i> Lihat Estimasi</a>
        <a href="{{ route('services.show', $createdEstimate->service_id) }}#tab-estimate" class="btn btn-sm btn-outline-secondary"><i class="fas fa-toolbox me-1"></i> Buka Service</a>
    </div>
</div>
@endif
@endif

{{-- Generic flash --}}
@if(session('success') && ! session('created_estimate_id'))
<div class="alert alert-success py-2"><i class="fas fa-check-circle me-1"></i>{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger py-2"><i class="fas fa-triangle-exclamation me-1"></i>{{ session('error') }}</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-file-signature text-warning me-2"></i>Estimasi Servis</h4>
    @if($canCreate)
    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#serviceSelectModal">
        <i class="fas fa-plus me-1"></i>+ Buat Estimasi
    </button>
    @endif
</div>

{{-- ============ SERVICE SELECT MODAL (searchable dropdown, stays in Estimasi) ============ --}}
@if($canCreate)
<div class="modal fade" id="serviceSelectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-file-signature me-1"></i> Pilih Service / Work Order</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="svcSelectSearch" class="form-control mb-2"
                       placeholder="Cari No Service / pelanggan / no HP / plat kendaraan..." autocomplete="off">
                <div class="d-flex gap-2 mb-2 flex-wrap" id="svcQuickFilters">
                    <button type="button" class="btn btn-sm btn-primary" data-filter="">Belum Ada Estimasi</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="all">Semua Service</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="draft">Ada Draft</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-filter="waiting">Menunggu Approval</button>
                </div>
                <div id="svcSelectResults" class="list-group list-group-flush overflow-auto" style="max-height:380px">
                    <div class="text-muted text-center py-3">Memuat...</div>
                </div>
                <div class="text-center mt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="svcLoadMore">Muat lebih banyak</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============================ SUMMARY CARDS ============================ --}}
<div class="row g-2 mb-3">
    @foreach($statusChips as $statusKey => $chip)
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ route('estimates.index', array_filter(array_merge(request()->query(), ['status' => $statusKey, 'page' => 1]), fn ($v) => $v !== null && $v !== '')) }}"
           class="card h-100 text-decoration-none {{ $activeStatus === $statusKey ? 'border-warning' : '' }}">
            <div class="card-body text-center py-2">
                <small class="text-muted d-block text-truncate">{{ $chip['label'] }}</small>
                <h4 class="mb-0 text-{{ $chip['color'] }}">{{ $chip['count'] }}</h4>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-body">
        {{-- ============================ FILTERS ============================ --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-lg-3 col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}"
                       placeholder="Cari no estimasi / no service / pelanggan / plat...">
            </div>
            <div class="col-lg-2 col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    @foreach(\App\Models\ServiceEstimate::STATUS_LABELS as $statusValue => $label)
                        <option value="{{ $statusValue }}" @selected($filters['status'] === $statusValue)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <select name="branch_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected($filters['branch_id'] == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-1 col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] }}" title="Tanggal dari">
            </div>
            <div class="col-6 col-lg-1 col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] }}" title="Tanggal sampai">
            </div>
            <div class="col-6 col-lg-1 col-md-2">
                <input type="date" name="valid_until" class="form-control form-control-sm" value="{{ $filters['valid_until'] }}" title="Berlaku sampai">
            </div>
            <div class="col-6 col-lg-1 col-md-2">
                <input type="number" name="version" min="1" class="form-control form-control-sm" value="{{ $filters['version'] }}" placeholder="Versi" title="Versi">
            </div>
            <div class="col-lg-1 col-md-2 d-grid gap-1">
                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="{{ route('estimates.index') }}" class="btn btn-sm btn-outline-danger"><i class="fas fa-rotate-left me-1"></i>Reset</a>
            </div>
        </form>

        {{-- ============================ TABLE ============================ --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>No Estimasi</th>
                        <th class="text-center">Versi</th>
                        <th>Tanggal</th>
                        <th>No Service</th>
                        <th>Pelanggan</th>
                        <th>Kendaraan / Plat</th>
                        <th class="text-end">Grand Total</th>
                        <th>Berlaku Sampai</th>
                        <th>Status</th>
                        <th>Tahap Service</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estimates as $estimate)
                    @php
                        $serviceUrl = route('services.show', $estimate->service_id).'#tab-estimate';
                        $detailUrl = $estimate->status === \App\Models\ServiceEstimate::STATUS_DRAFT
                            ? $serviceUrl
                            : route('estimates.preview', $estimate);
                        $invoiceModel = $estimate->invoice;
                        $issuable = $estimate->status !== \App\Models\ServiceEstimate::STATUS_DRAFT;
                        $sendable = in_array($estimate->status, [
                            \App\Models\ServiceEstimate::STATUS_DRAFT,
                            \App\Models\ServiceEstimate::STATUS_SENT,
                            \App\Models\ServiceEstimate::STATUS_WAITING_APPROVAL,
                            \App\Models\ServiceEstimate::STATUS_REJECTED,
                            \App\Models\ServiceEstimate::STATUS_EXPIRED,
                        ], true);
                        $revisable = in_array($estimate->status, [
                            \App\Models\ServiceEstimate::STATUS_SENT,
                            \App\Models\ServiceEstimate::STATUS_WAITING_APPROVAL,
                            \App\Models\ServiceEstimate::STATUS_APPROVED,
                            \App\Models\ServiceEstimate::STATUS_REJECTED,
                            \App\Models\ServiceEstimate::STATUS_EXPIRED,
                        ], true);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ $detailUrl }}" @unless($estimate->status === \App\Models\ServiceEstimate::STATUS_DRAFT) target="_blank" @endunless class="fw-semibold text-decoration-none">
                                {{ $estimate->estimate_number }}
                            </a>
                        </td>
                        <td class="text-center">
                            v{{ $estimate->version }}
                            @if($estimate->previous_estimate_id)<i class="fas fa-code-branch text-muted small ms-1" title="revisi"></i>@endif
                        </td>
                        <td>{{ $estimate->estimate_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            <a href="{{ $serviceUrl }}" class="text-decoration-none">{{ $estimate->service?->job_no ?? '-' }}</a>
                        </td>
                        <td>
                            {{ $estimate->customer?->name ?? '-' }}
                            @if($estimate->customer?->phone)<small class="text-muted d-block">{{ $estimate->customer->phone }}</small>@endif
                        </td>
                        <td>
                            @php $veh = $estimate->vehicle; @endphp
                            <span class="fw-semibold">{{ $veh?->number_plate ?? '-' }}</span>
                            <small class="text-muted d-block">{{ trim(($veh?->vehicleBrand?->vehicle_brand ?? '').' '.($veh?->model_name ?? '')) ?: '-' }}</small>
                        </td>
                        <td class="text-end">@include('partials.rupiah', ['amount' => $estimate->grand_total])</td>
                        <td>
                            {{ $estimate->valid_until?->format('d/m/Y') ?? '-' }}
                            @if($estimate->isExpiredByDate())<i class="fas fa-triangle-exclamation text-danger small ms-1" title="Jatuh tempo"></i>@endif
                        </td>
                        <td><span class="badge bg-{{ $estimate->statusColor() }}">{{ $estimate->statusLabel() }}</span></td>
                        @php $estimateProgress = app(\App\Services\WorkshopProgressService::class)->calculate($estimate->service); @endphp
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $estimateProgress['steps'][$estimateProgress['current_step']]['label'] }}</span></td>
                        <td class="text-end text-nowrap">
                            {{-- Always available: back to the work order --}}
                            <a href="{{ $serviceUrl }}" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Buka Servis"><i class="fas fa-toolbox"></i></a>

                            <a href="{{ route('estimates.pdf', $estimate) }}" class="btn btn-xs btn-outline-danger py-0 px-1" title="PDF"><i class="fas fa-file-pdf"></i></a>

                            @if($canSend && $sendable)
                            <form action="{{ route('estimates.send-wa', $estimate) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-xs btn-outline-success py-0 px-1" title="Kirim WhatsApp"><i class="fab fa-whatsapp"></i></button>
                            </form>
                            <form action="{{ route('estimates.send-email', $estimate) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-xs btn-outline-primary py-0 px-1" title="Kirim Email"><i class="fas fa-envelope"></i></button>
                            </form>
                            @endif

                            @if($estimate->status === \App\Models\ServiceEstimate::STATUS_DRAFT && auth()->user()?->can('estimates.update'))
                            <a href="{{ $serviceUrl }}" class="btn btn-xs btn-outline-warning py-0 px-1" title="Edit Draft"><i class="fas fa-edit"></i></a>
                            @endif

                            @if($issuable)
                            <a href="{{ route('estimates.preview', $estimate) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Preview"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('estimates.print', $estimate) }}" target="_blank" class="btn btn-xs btn-outline-dark py-0 px-1" title="Print"><i class="fas fa-print"></i></a>
                            @endif

                            @if($revisable && $canRevise)
                            <button type="button" class="btn btn-xs btn-outline-warning py-0 px-1" title="Buat Revisi"
                                    data-bs-toggle="modal" data-bs-target="#indexReviseModal"
                                    data-action="{{ route('estimates.revise', $estimate) }}"
                                    data-number="{{ $estimate->estimate_number }}"><i class="fas fa-code-branch"></i></button>
                            @endif

                            @if($estimate->status === \App\Models\ServiceEstimate::STATUS_APPROVED && $canConvert)
                            <form action="{{ route('estimates.convert-invoice', $estimate) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Buat Invoice dari estimasi ini? Estimasi akan menjadi dokumen historis.')">
                                @csrf
                                <button class="btn btn-xs btn-primary py-0 px-1" title="Convert ke Invoice"><i class="fas fa-file-invoice-dollar"></i></button>
                            </form>
                            @endif

                            @if($estimate->status === \App\Models\ServiceEstimate::STATUS_CONVERTED && $invoiceModel)
                            <a href="{{ route('invoices.show', $invoiceModel) }}" class="btn btn-xs btn-outline-primary py-0 px-1" title="Lihat Invoice"><i class="fas fa-file-invoice"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">Belum ada estimasi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $estimates->links('partials.pagination') }}
        </div>
    </div>
</div>

{{-- ============================ REVISE MODAL (shared) ============================ --}}
@if($canRevise)
<div class="modal fade" id="indexReviseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="indexReviseForm" method="POST" action="">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-code-branch me-1"></i> Buat Revisi <span id="indexReviseNumber"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Versi saat ini disimpan sebagai dokumen historis dan versi baru (draft) akan dibuat. Lengkapi item pada tab Estimasi service setelah revisi dibuat.</p>
                <label class="form-label small">Alasan revisi <span class="text-danger">*</span></label>
                <textarea name="revision_reason" rows="2" class="form-control form-control-sm" required placeholder="Contoh: ditemukan kerusakan tambahan"></textarea>
                <input type="hidden" name="use_current_items" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-warning">Buat Revisi</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var searchUrl = '{{ route('estimates.service-search') }}';
    var modal = document.getElementById('serviceSelectModal');
    if (! modal) { return; }

    var input = document.getElementById('svcSelectSearch');
    var results = document.getElementById('svcSelectResults');
    var loadMoreBtn = document.getElementById('svcLoadMore');
    var filterBtns = document.querySelectorAll('#svcQuickFilters button');
    var currentFilter = '';
    var currentPage = 1;
    var hasMore = false;
    var timer = null;
    var lastLoaded = [];

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function actionButton(s) {
        if (s.action === 'continue_draft') {
            return '<span class="btn btn-sm btn-warning">Lanjutkan Draft</span>';
        }
        if (s.action === 'view') {
            return '<span class="btn btn-sm btn-outline-secondary">Lihat Estimasi</span>';
        }
        if (s.action === 'revise') {
            return '<span class="btn btn-sm btn-outline-warning">Buat Revisi</span>';
        }
        return '<span class="btn btn-sm btn-warning">Buat Estimasi</span>';
    }

    function card(s) {
        var badge = s.has_active_estimate
            ? '<span class="badge bg-info ms-1">' + esc(s.estimate.status_label) + ' ' + esc(s.estimate.number) + '</span>'
            : '';
        var note = s.needs_inspection
            ? '<small class="text-warning d-block"><i class="fas fa-triangle-exclamation me-1"></i>Checklist/inspection belum selesai</small>'
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
            + '<div class="small text-muted mb-1">Status: ' + esc(s.workflow_label) + '</div>'
            + actionButton(s)
            + '</div>'
            + '</div>'
            + '</a>';
    }

    function load(reset) {
        if (reset) { currentPage = 1; }
        var params = new URLSearchParams({ q: input.value.trim(), filter: currentFilter, page: currentPage });
        results.querySelector('.svc-loading')?.remove();
        if (reset) {
            results.innerHTML = '<div class="text-muted text-center py-3 svc-loading">Memuat...</div>';
        }
        fetch(searchUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var html = (data.results || []).map(card).join('');
                if (reset) {
                    results.innerHTML = html !== ''
                        ? html
                        : (input.value.trim() !== ''
                            ? '<div class="text-muted text-center py-4">Tidak ditemukan Service yang cocok dengan pencarian.</div>'
                            : '<div class="text-muted text-center py-4">Belum ada Service / Work Order aktif.</div>');
                } else {
                    results.querySelector('.svc-loading')?.remove();
                    if (html !== '') { results.insertAdjacentHTML('beforeend', html); }
                }
                hasMore = !!(data.pagination && data.pagination.more);
                loadMoreBtn.classList.toggle('d-none', ! hasMore);
                results.querySelectorAll('.svc-loading').forEach(function (el) { el.remove(); });
            })
            .catch(function () {
                results.innerHTML = '<div class="text-danger text-center py-3">Gagal memuat. Coba lagi.</div>';
            });
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { load(true); }, 250);
    });
    filterBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterBtns.forEach(function (b) { b.className = 'btn btn-sm btn-outline-secondary'; });
            btn.className = 'btn btn-sm btn-primary';
            currentFilter = btn.getAttribute('data-filter');
            load(true);
        });
    });
    loadMoreBtn.addEventListener('click', function () {
        currentPage++;
        load(false);
    });
    modal.addEventListener('shown.bs.modal', function () {
        input.value = '';
        currentFilter = '';
        load(true);
        input.focus();
    });

    // ============ REVISE MODAL binding ============
    var reviseModal = document.getElementById('indexReviseModal');
    if (reviseModal) {
        reviseModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (! trigger) { return; }
            var action = trigger.getAttribute('data-action');
            var number = trigger.getAttribute('data-number') || '';
            document.getElementById('indexReviseForm').setAttribute('action', action);
            document.getElementById('indexReviseNumber').textContent = number;
        });
    }
})();
</script>
@endpush
