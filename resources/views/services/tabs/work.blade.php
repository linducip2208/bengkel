{{-- Tab: PEKERJAAN — work packages, tasks, standard vs actual time --}}
@php
    $embedded = $embedded ?? false;
    $packages = $service->workPackages->sortByDesc('id');
    $canStart = auth()->user()?->can('work-tasks.start');
    $canPerformQc = auth()->user()?->can('qc.perform');
    $isMechanic = auth()->user()?->hasRole('mekanik');
@endphp
<div class="{{ $embedded ? '' : 'tab-pane fade' }}" id="{{ $embedded ? 'finding-work-plans' : 'tab-work' }}">
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-sitemap me-2 text-warning"></i>Rencana Pekerjaan</h6>
            <p class="small text-muted">Rencana Pekerjaan menghubungkan Temuan ke Estimasi. Eksekusi teknisi tersedia di tab Pekerjaan setelah approval customer.</p>

            @forelse($packages as $package)
            @php
                $task = $package->task;
                $totals = $package->computeTotals();
                $showPrices = ! $isMechanic || auth()->user()?->can('estimates.view');
            @endphp
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>{{ $package->title }}</strong>
                        <span class="badge bg-light text-dark ms-1">{{ \App\Models\ServiceWorkPackage::STATUS_LABELS[$package->status] ?? $package->status }}</span>
                        @if($package->finding)
                            <small class="text-muted d-block">Sumber: {{ $package->finding->finding_number }} — {{ \App\Models\ServiceFinding::SEVERITY_LABELS[$package->finding->severity] ?? '' }}</small>
                        @else
                            <small class="text-muted d-block">Sumber: manual</small>
                        @endif
                    </div>
                    @if($showPrices)
                    <div class="text-end small">
                        <div>Jasa: <strong>Rp {{ number_format($totals['labor_total'], 0, ',', '.') }}</strong></div>
                        <div>Part: <strong>Rp {{ number_format($totals['part_total'], 0, ',', '.') }}</strong></div>
                        <div>Total: <strong>Rp {{ number_format($totals['grand_total'], 0, ',', '.') }}</strong></div>
                    </div>
                    @endif
                </div>

                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered mb-2">
                        <thead class="table-light"><tr><th>Tipe</th><th>Deskripsi</th><th class="text-center">Qty</th><th class="text-center">Std Time</th>@if($showPrices)<th class="text-end">Harga</th><th class="text-end">Total</th>@endif</tr></thead>
                        <tbody>
                            @foreach($package->items as $item)
                            <tr>
                                <td><span class="badge bg-light text-dark">{{ ['labor' => 'Jasa', 'part' => 'Part', 'other' => 'Lain'][$item->item_type] ?? $item->item_type }}</span></td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-center">{{ $item->standard_minutes > 0 ? $item->standard_minutes.' mnt' : '-' }}</td>
                                @if($showPrices)
                                <td class="text-end">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($task)
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-top pt-2">
                    <div class="small">
                        <span class="badge bg-light text-dark">{{ \App\Models\ServiceWorkTask::STATUS_LABELS[$task->status] ?? $task->status }}</span>
                        <span class="text-muted ms-2">Standard: <strong>{{ $task->standard_minutes }} menit</strong></span>
                        <span class="text-muted ms-2">Actual: <strong>{{ $task->actualMinutes() }} menit</strong></span>
                        @if($task->assignee)<span class="text-muted ms-2">Teknisi: {{ $task->assignee->name }}</span>@endif
                    </div>
                    <div class="d-flex gap-1">
                        @if($canStart && in_array($task->status, ['ready', 'paused', 'pending'], true))
                        <form action="{{ route('work-tasks.start', $task) }}" method="POST">@csrf <button class="btn btn-sm btn-success">Start</button></form>
                        @endif
                        @if($canStart && $task->status === 'in_progress')
                        <form action="{{ route('work-tasks.pause', $task) }}" method="POST">@csrf <button class="btn btn-sm btn-outline-warning">Pause</button></form>
                        <form action="{{ route('work-tasks.finish', $task) }}" method="POST">@csrf <button class="btn btn-sm btn-outline-dark">Finish</button></form>
                        @endif
                        @if($canPerformQc && in_array($task->status, ['qc_pending', 'qc_failed'], true))
                        <a href="{{ route('work-packages.qc', $service) }}#package-{{ $package->id }}" class="btn btn-sm btn-primary">QC</a>
                        @endif
                    </div>
                </div>
                @elseif(in_array($package->status, ['approved', 'proposed'], true))
                <div class="small text-muted border-top pt-2">Menunggu persetujuan customer / pembuatan task.</div>
                @endif
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="fas fa-briefcase fa-2x mb-2"></i>
                <p class="mb-0">Belum ada rencana pekerjaan. Buat dari tab Temuan.</p>
            </div>
            @endforelse

            {{-- Manual package quick-create --}}
            @if(auth()->user()?->can('work-packages.create'))
            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#wpModal">
                <i class="fas fa-plus me-1"></i> Tambah Rencana Pekerjaan Manual
            </button>
            @endif
        </div>
    </div>
</div>
