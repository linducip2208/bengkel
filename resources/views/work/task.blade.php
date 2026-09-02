<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pekerjaan Saya — {{ $task->workPackage->title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .card { border-radius: 14px; border: none; box-shadow: 0 2px 10px rgba(0,0,0,.06); }
    </style>
</head>
<body class="py-4">
    <div class="container" style="max-width: 720px">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-1">{{ $task->workPackage->title }}</h5>
                        <small class="text-muted d-block">{{ $task->service->job_no }} — {{ $task->service->customer->name ?? '' }}</small>
                        @if($task->workPackage->finding)
                        <small class="text-muted d-block">Sumber: {{ $task->workPackage->finding->finding_number }}</small>
                        @endif
                    </div>
                    <span class="badge bg-primary">{{ \App\Models\ServiceWorkTask::STATUS_LABELS[$task->status] ?? $task->status }}</span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1 small"><span>Standard:</span><strong>{{ $task->standard_minutes }} menit</strong></div>
                <div class="d-flex justify-content-between mb-3 small"><span>Actual:</span><strong id="actualMinutes">{{ $task->actualMinutes() }} menit</strong></div>

                <div class="d-grid gap-2">
                    @if(in_array($task->status, ['ready', 'pending', 'paused', 'qc_failed']))
                    <form method="POST" action="{{ route('work-tasks.start', $task) }}">
                        @csrf
                        <button class="btn btn-success w-100 btn-lg">{{ $task->status === 'paused' ? 'RESUME' : 'START' }}</button>
                    </form>
                    @endif
                    @if($task->status === 'in_progress')
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('work-tasks.pause', $task) }}" class="w-50">@csrf
                            <button class="btn btn-outline-warning w-100">PAUSE</button>
                        </form>
                        <form method="POST" action="{{ route('work-tasks.finish', $task) }}" class="w-50" onsubmit="return confirm('Selesaikan pekerjaan ini dan kirim ke QC?')">@csrf
                            <button class="btn btn-dark w-100">FINISH</button>
                        </form>
                    </div>
                    @endif
                </div>
                @if($task->status === 'qc_pending')
                <div class="alert alert-info text-center mb-0 mt-2 mb-0 small">Menunggu pemeriksaan QC.</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="border-bottom pb-2">Parts</h6>
                @forelse($task->workPackage->items->where('item_type', 'part') as $item)
                <div class="d-flex justify-content-between small border-bottom py-1">
                    <span>{{ $item->description }} × {{ $item->quantity }}</span>
                    <span class="text-success">✓ tersedia</span>
                </div>
                @empty
                <p class="text-muted small mb-0">Tidak ada parts.</p>
                @endforelse
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="{{ url('/') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
</body>
</html>
