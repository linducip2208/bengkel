@extends('layouts.app')

@section('title', 'Checklist Kendaraan')

@php
    $progress = app(\App\Services\WorkshopFlowService::class)->checklistProgress($service);
    $canUpdate = $canUpdate ?? false;
    $readOnly = ! $canUpdate;
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-11">
        {{-- ============================ HEADLINE ============================ --}}
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-1"><i class="fas fa-clipboard-check text-danger me-2"></i>CHECKLIST KENDARAAN</h5>
                        <div class="d-flex flex-wrap gap-3 small">
                            <span class="fw-semibold">{{ $service->job_no }}</span>
                            <span>{{ $service->customer?->name ?? '-' }}</span>
                            <span>{{ trim(($service->vehicle?->vehicleBrand?->vehicle_brand ?? '').' '.($service->vehicle?->model_name ?? '')) }}</span>
                            <span class="badge bg-light text-dark">{{ $service->vehicle?->number_plate ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('observations.checklist.print', $service) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-print me-1"></i> Print Checklist
                        </a>
                        <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('observations.save-checklist', $service) }}" method="POST" id="checklistForm">
            @csrf
            <fieldset {{ $readOnly ? 'disabled' : '' }}>
            <div class="row g-3">
                {{-- ============================ LEFT: KONDISI KOMPONEN ============================ --}}
                <div class="col-lg-7">
                    <div class="card h-100">
                        <div class="card-header"><i class="fas fa-list-check me-2 text-danger"></i><strong>KONDISI KOMPONEN</strong></div>
                        <div class="card-body">
                            @foreach($groupedPoints as $type => $points)
                            <h6 class="border-bottom pb-2 mt-3 text-uppercase">{{ $type }}</h6>
                            @foreach($points as $point)
                            @php $result = $checkResults->get($point->id); $current = $result?->condition_status ?? 'not_checked'; @endphp
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                                    <strong>{{ $point->observation_point }}</strong>
                                    <select name="points[{{ $point->id }}][condition_status]" class="form-select form-select-sm condition-select" style="max-width:180px"
                                            data-point="{{ $point->id }}">
                                        @foreach(\App\Models\ServiceObservationPoint::CONDITION_LABELS as $value => $label)
                                            <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="row g-1 mt-1">
                                    <div class="col-md-6">
                                        <input type="text" name="points[{{ $point->id }}][comment]" class="form-control form-control-sm"
                                               value="{{ $result->comment ?? '' }}" placeholder="Catatan: {{ $point->observation_point }}...">
                                    </div>
                                    <div class="col-4 col-md-3">
                                        <input type="number" step="0.001" min="0" name="points[{{ $point->id }}][measurement_value]" class="form-control form-control-sm"
                                               value="{{ $result?->measurement_value !== null ? rtrim(rtrim(number_format((float) $result->measurement_value, 3, '.', ''), '0'), '.') : '' }}"
                                               placeholder="Ukur">
                                    </div>
                                    <div class="col-8 col-md-3">
                                        <input type="text" name="points[{{ $point->id }}][measurement_unit]" class="form-control form-control-sm"
                                               value="{{ $result->measurement_unit ?? '' }}" placeholder="mm / V">
                                    </div>
                                </div>
                                @php $evidence = $result?->mediaAttachments ?? collect(); @endphp
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <label class="small text-muted mb-0" style="cursor:pointer" title="Foto bukti (via media)">
                                        <i class="fas fa-camera me-1"></i> Foto
                                        <input type="file" accept="image/*" class="d-none point-photo"
                                               data-point-id="{{ $result?->id }}" {{ $result === null ? 'disabled' : '' }}>
                                    </label>
                                    <span class="photo-feedback small text-muted">
                                        @if($evidence->isNotEmpty())<i class="fas fa-check text-success me-1"></i>{{ $evidence->count() }} foto @endif
                                    </span>
                                </div>
                                @if($result === null)
                                <div class="small text-muted mt-1"><i class="fas fa-info-circle me-1"></i>Simpan checklist terlebih dahulu untuk melampirkan foto poin ini.</div>
                                @endif
                            </div>
                            @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ============================ RIGHT: RINGKASAN ============================ --}}
                <div class="col-lg-5">
                    <div class="card position-sticky" style="top: 80px">
                        <div class="card-header"><i class="fas fa-gauge-high me-2 text-danger"></i><strong>RINGKASAN PEMERIKSAAN</strong></div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Item diperiksa</span>
                                <strong><span id="checkedCount">{{ $progress['checked_count'] }}</span> dari <span id="totalCount">{{ $progress['total_points'] }}</span></strong>
                            </div>
                            <div class="progress mb-3" style="height: 10px">
                                <div class="progress-bar bg-danger" id="progressBar" style="width: {{ $progress['percentage'] }}%">{{ $progress['percentage'] }}%</div>
                            </div>

                            <table class="table table-sm mb-3" id="statusSummary">
                                <tbody>
                                    <tr><td><span class="badge bg-danger">Kritis</span></td><td class="text-end" data-status="critical">{{ $progress['by_status']['critical'] }}</td></tr>
                                    <tr><td><span class="badge bg-warning text-dark">Perlu Perbaikan</span></td><td class="text-end" data-status="repair_required">{{ $progress['by_status']['repair_required'] }}</td></tr>
                                    <tr><td><span class="badge bg-warning bg-opacity-50 text-dark">Perlu Perhatian</span></td><td class="text-end" data-status="attention">{{ $progress['by_status']['attention'] }}</td></tr>
                                    <tr><td><span class="badge bg-success">OK</span></td><td class="text-end" data-status="ok">{{ $progress['by_status']['ok'] }}</td></tr>
                                    <tr><td><span class="badge bg-secondary">Belum Diperiksa</span></td><td class="text-end" data-status="not_checked">{{ $progress['by_status']['not_checked'] }}</td></tr>
                                </tbody>
                            </table>

                            <div class="alert alert-danger small d-none" id="criticalWarning">
                                <strong><i class="fas fa-triangle-exclamation me-1"></i>PERHATIAN</strong><br>
                                Ditemukan <span id="criticalCount">{{ $progress['critical_count'] }}</span> item kritis.
                                Pastikan Service Advisor memahami temuan sebelum mengirim estimasi.
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-outline-secondary" name="action" value="draft">
                                    <i class="fas fa-save me-1"></i> Simpan sebagai Draft
                                </button>
                                <button type="submit" class="btn btn-danger" name="action" value="continue">
                                    <i class="fas fa-arrow-right me-1"></i> Lanjut ke Temuan / Estimasi
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">Kedua tombol menyimpan checklist — pemeriksaan tidak akan hilang. Pemeriksaan belum lengkap tetap boleh disimpan.</small>
                            @if($readOnly)
                            <div class="alert alert-secondary small mt-2 mb-0">
                                <i class="fas fa-lock me-1"></i> Anda hanya dapat melihat checklist ini. Hubungi Service Advisor atau Mekanik untuk perubahan.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            </fieldset>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var CONDITIONS = ['not_checked', 'ok', 'attention', 'repair_required', 'critical'];

    function refreshSummary() {
        var counts = {};
        CONDITIONS.forEach(function (c) { counts[c] = 0; });
        var selects = document.querySelectorAll('.condition-select');
        selects.forEach(function (s) { counts[s.value] = (counts[s.value] || 0) + 1; });

        var checked = counts.ok + counts.attention + counts.repair_required + counts.critical;
        var total = selects.length;
        var pct = total > 0 ? Math.round(checked / total * 100) : 0;

        document.getElementById('checkedCount').textContent = checked;
        document.getElementById('totalCount').textContent = total;
        var bar = document.getElementById('progressBar');
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';
        bar.className = 'progress-bar ' + (counts.critical > 0 ? 'bg-danger' : (pct < 100 ? 'bg-warning' : 'bg-success'));

        CONDITIONS.forEach(function (c) {
            var cell = document.querySelector('[data-status="' + c + '"]');
            if (cell) { cell.textContent = counts[c] || 0; }
        });

        var warning = document.getElementById('criticalWarning');
        document.getElementById('criticalCount').textContent = counts.critical || 0;
        warning.classList.toggle('d-none', !(counts.critical > 0));
    }

    // Incomplete-checklist warning: confirm before submitting "continue".
    function confirmContinue() {
        var selects = document.querySelectorAll('.condition-select');
        var unchecked = 0;
        selects.forEach(function (s) { if (s.value === 'not_checked') { unchecked++; } });
        if (unchecked > 0) {
            return window.confirm('Checklist belum lengkap (' + unchecked + ' poin belum diperiksa). Lanjutkan?');
        }
        return true;
    }

    // Both submit buttons save first — "continue" additionally confirms
    // when the checklist is intentionally incomplete. State is never lost.
    document.querySelectorAll('#checklistForm button[value="continue"]').forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            if (! confirmContinue()) {
                event.preventDefault();
            }
        });
    });

    // Persist point rows on photo pick via existing MediaAttachment upload.
    document.querySelectorAll('.point-photo').forEach(function (input) {
        input.addEventListener('change', function () {
            if (! this.files.length) { return; }
            var pointId = this.getAttribute('data-point-id');
            var fd = new FormData();
            fd.append('attachable_type', 'observation_point');
            fd.append('attachable_id', pointId);
            fd.append('name', 'Foto checklist');
            fd.append('file', this.files[0]);
            fetch('{{ route('media.store') }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function () { window.location.reload(); })
              .catch(function () { window.location.reload(); });
        });
    });

    document.addEventListener('DOMContentLoaded', refreshSummary);
    document.querySelectorAll('.condition-select').forEach(function (s) {
        s.addEventListener('change', refreshSummary);
    });
})();
</script>
@endpush
