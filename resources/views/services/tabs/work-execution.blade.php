{{-- Tab: PEKERJAAN — execution only; planning stays in Rencana Pekerjaan. --}}
@php
    $tasks = $service->workTasks->loadMissing(['workPackage.finding', 'assignee']);
    $canStart = auth()->user()?->can('work-tasks.start');
    $canPerformQc = auth()->user()?->can('qc.perform');
@endphp
<div class="tab-pane fade" id="tab-work-execution">
    <div class="card"><div class="card-body">
        <h6 class="mb-2"><i class="fas fa-tools me-2 text-primary"></i>Pekerjaan Teknisi</h6>
        <p class="small text-muted">Work Task dibuat dari Work Package yang disetujui customer.</p>
        @forelse($tasks as $task)
            @php $package = $task->workPackage; @endphp
            <div class="border rounded p-3 mb-3" id="task-{{ $task->id }}">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div><strong>{{ $package?->title ?? 'Work Package' }}</strong><div class="small text-muted">{{ $package?->finding?->finding_number ? 'Sumber '.$package->finding->finding_number.' · ' : '' }}Persetujuan: <span class="badge bg-success">Disetujui</span></div></div>
                    <span class="badge bg-light text-dark">{{ \App\Models\ServiceWorkTask::STATUS_LABELS[$task->status] ?? $task->status }}</span>
                </div>
                <div class="row g-2 small mt-2">
                    <div class="col-md-3">Teknisi: <strong>{{ $task->assignee?->name ?? 'Belum ditugaskan' }}</strong></div>
                    <div class="col-md-3">Standar: <strong>{{ $task->standard_minutes }} menit</strong></div>
                    <div class="col-md-3">Aktual: <strong>{{ $task->actualMinutes() }} menit</strong></div>
                    <div class="col-md-3 text-md-end">
                        @if($canStart && in_array($task->status, [\App\Models\ServiceWorkTask::STATUS_READY, \App\Models\ServiceWorkTask::STATUS_PAUSED], true))
                            <form action="{{ route('work-tasks.start', $task) }}" method="POST" class="d-inline">@csrf <button class="btn btn-sm btn-success"><i class="fas fa-play me-1"></i>{{ $task->status === 'paused' ? 'RESUME' : 'START' }}</button></form>
                        @endif
                        @if($canStart && $task->status === \App\Models\ServiceWorkTask::STATUS_IN_PROGRESS)
                            <form action="{{ route('work-tasks.pause', $task) }}" method="POST" class="d-inline">@csrf <button class="btn btn-sm btn-outline-warning"><i class="fas fa-pause me-1"></i>PAUSE</button></form>
                            <form action="{{ route('work-tasks.finish', $task) }}" method="POST" class="d-inline">@csrf <button class="btn btn-sm btn-outline-dark"><i class="fas fa-stop me-1"></i>FINISH</button></form>
                        @endif
                        @if($canPerformQc && in_array($task->status, [\App\Models\ServiceWorkTask::STATUS_QC_PENDING, \App\Models\ServiceWorkTask::STATUS_QC_FAILED], true))
                            <a href="{{ route('work-packages.qc', $service) }}#task-{{ $task->id }}" class="btn btn-sm btn-primary"><i class="fas fa-clipboard-check me-1"></i>QC</a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-warning mb-0"><i class="fas fa-lock me-1"></i>Belum ada pekerjaan yang disetujui customer.</div>
        @endforelse
    </div></div>
</div>
