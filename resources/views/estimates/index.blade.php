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
    <a href="{{ route('estimates.create') }}" class="btn btn-warning">
        <i class="fas fa-plus me-1"></i>+ Buat Estimasi
    </a>
    @endif
</div>

{{-- ============================ SUMMARY CARDS ============================ --}}
<div class="row g-2 mb-3">
    @foreach($statusChips as $statusKey => $chip)
    <div class="col-6 col-md-4 col-lg">
        <a href="{{ route('estimates.index', array_filter(array_merge(request()->query(), ['status' => $statusKey, 'page' => 1]), fn ($v) => $v !== null && $v !== '')) }}"
           class="card h-100 text-decoration-none {{ $activeStatus === $statusKey ? 'border-primary shadow-sm' : '' }}" aria-current="{{ $activeStatus === $statusKey ? 'true' : 'false' }}">
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
            <details class="col-lg-2 col-md-4">
                <summary class="btn btn-sm btn-outline-secondary w-100" style="cursor: pointer; list-style: none;">Filter lanjutan</summary>
                <div class="row g-2 mt-1">
                    <div class="col-6">
                        <input type="date" name="valid_until" class="form-control form-control-sm" value="{{ $filters['valid_until'] }}" title="Berlaku sampai" aria-label="Berlaku sampai">
                    </div>
                    <div class="col-6">
                        <input type="number" name="version" min="1" class="form-control form-control-sm" value="{{ $filters['version'] }}" placeholder="Versi" title="Versi" aria-label="Versi">
                    </div>
                </div>
            </details>
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
                        @php $service = $estimate->service; $estimateProgress = $service && ! $service->trashed() ? app(\App\Services\WorkshopProgressService::class)->calculate($service) : null; @endphp
                        <td>
                            @if($estimateProgress)
                                <span class="badge bg-primary bg-opacity-10 text-primary">{{ $estimateProgress['steps'][$estimateProgress['current_step']]['label'] }}</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary" title="Service tidak tersedia">Service tidak tersedia</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            {{-- Always available: back to the work order --}}
                            <a href="{{ $serviceUrl }}" class="btn btn-sm btn-outline-secondary" title="Buka Servis"><i class="fas fa-toolbox"></i></a>

                            <a href="{{ route('estimates.pdf', $estimate) }}" class="btn btn-sm btn-outline-danger" title="PDF"><i class="fas fa-file-pdf"></i></a>

                            @if($canSend && $sendable)
                            <form action="{{ route('estimates.send-wa', $estimate) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" title="Kirim WhatsApp"><i class="fab fa-whatsapp"></i></button>
                            </form>
                            <form action="{{ route('estimates.send-email', $estimate) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary" title="Kirim Email"><i class="fas fa-envelope"></i></button>
                            </form>
                            @endif

                            @if($estimate->status === \App\Models\ServiceEstimate::STATUS_DRAFT && auth()->user()?->can('estimates.update'))
                            <a href="{{ $serviceUrl }}" class="btn btn-sm btn-outline-warning" title="Edit Draft"><i class="fas fa-edit"></i></a>
                            @endif

                            @if($issuable)
                            <a href="{{ route('estimates.preview', $estimate) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Preview"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('estimates.print', $estimate) }}" target="_blank" class="btn btn-sm btn-outline-dark" title="Print"><i class="fas fa-print"></i></a>
                            @endif

                            @if($revisable && $canRevise)
                            <button type="button" class="btn btn-sm btn-outline-warning" title="Buat Revisi"
                                    data-bs-toggle="modal" data-bs-target="#indexReviseModal"
                                    data-action="{{ route('estimates.revise', $estimate) }}"
                                    data-number="{{ $estimate->estimate_number }}"><i class="fas fa-code-branch"></i></button>
                            @endif

                            @if($estimate->status === \App\Models\ServiceEstimate::STATUS_APPROVED && $canConvert)
                            <form action="{{ route('estimates.convert-invoice', $estimate) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Buat Invoice dari estimasi ini? Estimasi akan menjadi dokumen historis.')">
                                @csrf
                                <button class="btn btn-sm btn-primary" title="Convert ke Invoice"><i class="fas fa-file-invoice-dollar"></i></button>
                            </form>
                            @endif

                            @if($estimate->status === \App\Models\ServiceEstimate::STATUS_CONVERTED && $invoiceModel)
                            <a href="{{ route('invoices.show', $invoiceModel) }}" class="btn btn-sm btn-outline-primary" title="Lihat Invoice"><i class="fas fa-file-invoice"></i></a>
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
    // Legacy service-search creation flow removed (was inert behind if(false)).
    // Service-linked creation remains available through ?service_id=.

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
