@extends('layouts.app')

@section('title', 'QC Pekerjaan')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="fas fa-award text-primary me-2"></i>QC Pekerjaan — {{ $service->job_no }}</h4>
            <a href="{{ route('services.show', $service) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>

        @forelse($awaiting as $package)
        @php $task = $package->task; @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <strong>{{ $package->title }}</strong>
                <span class="badge {{ $package->status === 'qc_failed' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ \App\Models\ServiceWorkPackage::STATUS_LABELS[$package->status] ?? $package->status }}</span>
            </div>
            <div class="card-body">
                <div class="row small mb-3">
                    <div class="col-md-6">
                        @if($package->finding)
                        <div><strong>Original Finding:</strong>
                            <span class="badge {{ $package->finding->severity === 'critical' ? 'bg-danger' : 'bg-warning text-dark' }}">{{ $package->finding->title }}</span>
                            @if($package->finding->measurement_value !== null)
                                <span class="ms-1">{{ $package->finding->measurement_value }}{{ $package->finding->measurement_unit ? ' '.$package->finding->measurement_unit : '' }}</span>
                            @endif
                        </div>
                        @endif
                        @if($latestCheck = $package->qcChecks->first())
                        <div class="mt-1 {{ $latestCheck->result === 'passed' ? 'text-success' : 'text-danger' }}">
                            QC terakhir ({{ $latestCheck->result === 'passed' ? 'PASS' : 'FAIL' }}): {{ $latestCheck->notes }}
                        </div>
                        @endif
                    </div>
                    <div class="col-md-6 text-md-end">
                        @if($task)
                        <div>Standard: <strong>{{ $task->standard_minutes }} menit</strong></div>
                        <div>Actual: <strong>{{ $task->actualMinutes() }} menit</strong></div>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('work-packages.qc.store', $package) }}" class="row g-2 border-top pt-3">
                    @csrf
                    <div class="col-md-7">
                        <label class="form-label small">QC Checklist / Catatan</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Contoh: instalasi dicek, pedal normal, tidak ada suara abnormal, road test selesai..."></textarea>
                    </div>
                    <div class="col-md-5 d-flex align-items-end gap-2">
                        <button type="submit" name="result" value="passed" class="btn btn-success flex-fill"
                                onclick="return confirm('Lolos QC? Temuan terkait akan diselesaikan.')">
                            <i class="fas fa-check me-1"></i> PASS
                        </button>
                        <button type="submit" name="result" value="failed" class="btn btn-danger flex-fill"
                                onclick="return this.form.notes.value.trim() !== '' || (alert('Alasan QC gagal wajib diisi.'), false)">
                            <i class="fas fa-times me-1"></i> FAIL
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="card"><div class="card-body text-center text-muted py-4">
            <i class="fas fa-award fa-2x mb-2"></i>
            <p class="mb-0">Tidak ada pekerjaan yang menunggu QC.</p>
        </div></div>
        @endforelse

        @if($history->isNotEmpty())
        <h6 class="mt-4 mb-2">Riwayat QC Lolos</h6>
        @foreach($history as $package)
        <div class="d-flex justify-content-between border-bottom py-1 small">
            <span><i class="fas fa-check-circle text-success me-1"></i>{{ $package->title }}</span>
            <span class="text-muted">{{ $package->qcChecks->first()?->checked_at?->format('d/m/Y H:i') }}</span>
        </div>
        @endforeach
        @endif
    </div>
</div>
@endsection
