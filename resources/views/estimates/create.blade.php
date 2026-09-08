@extends('layouts.app')

@section('title', 'Buat Estimasi Servis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="text-muted small text-uppercase fw-semibold">Informasi harga pelanggan</div>
        <h4 class="mb-0"><i class="fas fa-file-signature text-warning me-2"></i>Buat Estimasi Servis</h4>
    </div>
    <a href="{{ route('estimates.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Daftar Estimasi</a>
</div>

@if(session('error'))
<div class="alert alert-danger py-2"><i class="fas fa-triangle-exclamation me-1"></i>{{ session('error') }}</div>
@endif

@if($service === null)
<div class="card border-warning">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
                <div class="text-muted small text-uppercase fw-semibold">Quotation customer</div>
                <h6 class="mb-1"><i class="fas fa-file-signature me-2 text-warning"></i>Buat Estimasi Langsung</h6>
                <p class="small text-muted mb-0">Pilih customer, kendaraan, keluhan, lalu masukkan item seperti Invoice. Sistem membuat konteks Check-In/WO otomatis saat disimpan.</p>
            </div>
            <span class="badge bg-warning text-dark"><i class="fas fa-bolt me-1"></i>Fast path</span>
        </div>
        @include('estimates.partials.builder', [
            'builderEstimate' => null,
            'availablePackages' => collect(),
            'directEstimate' => true,
            'builderAction' => route('estimates.direct.store'),
            'builderCancelUrl' => route('estimates.index'),
            'customers' => $customers,
            'vehicles' => $vehicles,
            'canOverridePrice' => auth()->user()?->can('pos.price_override'),
        ])
    </div>
</div>
@elseif($lockedEstimate)
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong class="fs-5">{{ $service->job_no }}</strong>
                <div class="small text-muted">{{ $service->customer?->name ?? '-' }} · {{ $service->vehicle?->number_plate ?? '-' }}</div>
            </div>
            <a href="{{ route('estimates.create') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-repeat me-1"></i>Ganti WO</a>
        </div>
    </div>
</div>
<div class="card border-warning">
    <div class="card-body text-center py-5">
        <i class="fas fa-file-circle-check fa-3x text-warning mb-3"></i>
        <h5>Service ini sudah memiliki estimasi terbit</h5>
        <p class="text-muted">{{ $lockedEstimate->estimate_number }} v{{ $lockedEstimate->version }} · <span class="badge bg-{{ $lockedEstimate->statusColor() }}">{{ $lockedEstimate->statusLabel() }}</span></p>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="{{ route('estimates.preview', $lockedEstimate) }}" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-eye me-1"></i>Lihat Estimasi</a>
            @if($lockedEstimate->status === \App\Models\ServiceEstimate::STATUS_CONVERTED && $lockedEstimate->invoice)
                <a href="{{ route('invoices.show', $lockedEstimate->invoice) }}" class="btn btn-outline-primary"><i class="fas fa-file-invoice me-1"></i>Lihat Invoice</a>
            @elseif(auth()->user()?->can('estimates.revise'))
                <a href="{{ route('services.show', $service) }}#tab-estimate" class="btn btn-warning"><i class="fas fa-code-branch me-1"></i>Buat Revisi</a>
            @endif
            <a href="{{ route('services.show', $service) }}#tab-estimate" class="btn btn-outline-secondary"><i class="fas fa-toolbox me-1"></i>Buka Service</a>
        </div>
    </div>
</div>
@else
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <strong class="fs-5">{{ $service->job_no }}</strong>
                <div class="small text-muted d-flex flex-wrap gap-3 mt-1">
                    <span><i class="fas fa-user me-1"></i>{{ $service->customer?->name ?? '-' }} <span class="badge bg-light text-dark">otomatis</span></span>
                    <span><i class="fas fa-car me-1"></i>{{ trim(($service->vehicle?->vehicleBrand?->vehicle_brand ?? '').' '.($service->vehicle?->model_name ?? '')) ?: '-' }} · <strong>{{ $service->vehicle?->number_plate ?? '-' }}</strong> <span class="badge bg-light text-dark">otomatis</span></span>
                    <span><i class="fas fa-timeline me-1"></i>{{ \App\Models\Service::WORKFLOW_LABELS[$service->workflow_status] ?? $service->workflow_status }}</span>
                </div>
            </div>
            <a href="{{ route('estimates.create') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-repeat me-1"></i>Ganti WO</a>
        </div>
    </div>
</div>

@if($continuingDraft)
<div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><strong><i class="fas fa-file-pen me-1"></i>Lanjutkan Draft</strong> — estimasi <strong>{{ $continuingDraft->estimate_number }}</strong> (v{{ $continuingDraft->version }}) sudah ada untuk service ini. Simpan akan memperbarui draft yang sama, bukan membuat baru.</div>
    <a href="{{ route('estimates.preview', $continuingDraft) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-eye me-1"></i>Lihat Draft</a>
</div>
@endif

@if((int) $service->workflow_status < 2)
<div class="alert alert-info py-2"><i class="fas fa-circle-info me-1"></i>Service belum diperiksa. Estimasi dapat dibuat sebagai informasi awal.</div>
@endif

@if($findings->isNotEmpty())
<div class="card mb-3">
    <div class="card-header"><strong><i class="fas fa-magnifying-glass me-2 text-warning"></i>TEMUAN</strong> <small class="text-muted">— referensi pemeriksaan, bukan harga</small></div>
    <div class="card-body py-2">
        @foreach($findings as $finding)
        <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2 small">
            <span><span class="badge bg-warning text-dark me-1">{{ $finding->finding_number }}</span><strong>{{ $finding->title }}</strong>@if($finding->measurement_value !== null) <span class="text-muted">{{ $finding->measurement_value }} {{ $finding->measurement_unit }}</span>@endif</span>
            <span class="badge bg-light text-dark">{{ \App\Models\ServiceFinding::STATUS_LABELS[$finding->status] ?? $finding->status }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

@include('estimates.partials.builder', ['builderEstimate' => $continuingDraft, 'availablePackages' => $packages, 'canOverridePrice' => auth()->user()?->can('pos.price_override')])
@endif
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    const input = document.getElementById('woSearch');
    const results = document.getElementById('woResults');
    if (!input || !results) return;
    const url = @json(route('estimates.service-search'));
    let timer = null;
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    function action(item) {
        const labels = {continue_draft:'Lanjutkan Draft', view:'Lihat Estimasi', revise:'Buat Revisi', create:'Buat Estimasi'};
        const styles = {continue_draft:'warning', view:'outline-secondary', revise:'outline-warning', create:'warning'};
        return '<span class="btn btn-sm btn-' + (styles[item.action] || 'warning') + '">' + labels[item.action] + '</span>';
    }
    function load() {
        const query = input.value.trim();
        results.innerHTML = '<div class="text-center text-muted py-4">Memuat...</div>';
        fetch(url + '?q=' + encodeURIComponent(query) + '&filter=all', {headers:{Accept:'application/json'}})
            .then(response => response.json())
            .then(data => {
                const items = data.results || [];
                results.innerHTML = items.length ? items.map(item => '<a href="' + esc(item.url) + '" class="list-group-item list-group-item-action"><div class="d-flex justify-content-between align-items-center gap-3"><div><strong>' + esc(item.job_no) + '</strong><div class="small">' + esc(item.customer || '-') + (item.phone ? ' · ' + esc(item.phone) : '') + '</div><small class="text-muted">' + esc(item.model || '-') + ' · <strong>' + esc(item.plate || '-') + '</strong></small>' + (item.needs_inspection ? '<small class="text-warning d-block"><i class="fas fa-circle-info me-1"></i>Service belum diperiksa.</small>' : '') + '</div><div class="text-end"><small class="text-muted d-block mb-1">' + esc(item.workflow_label) + '</small>' + action(item) + '</div></div></a>').join('') : '<div class="text-center text-muted py-4">Tidak ada Service / Work Order yang cocok.</div>';
            })
            .catch(() => { results.innerHTML = '<div class="text-center text-danger py-4">Gagal memuat work order. Coba lagi.</div>'; });
    }
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); });
    load();
})();
</script>
@endpush
