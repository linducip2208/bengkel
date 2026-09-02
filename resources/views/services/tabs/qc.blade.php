{{-- Tab: QC — validate completed work packages --}}
@php
    $qcPackages = $service->workPackages->whereIn('status', ['completed', 'qc_failed', 'qc_passed'])->sortByDesc('id');
    $canPerformQc = auth()->user()?->can('qc.perform');
@endphp
<div class="tab-pane fade" id="tab-qc">
    <div class="card">
        <div class="card-body">
            <h6 class="mb-3"><i class="fas fa-clipboard-check me-2 text-primary"></i>QC Pekerjaan</h6>

            @forelse($qcPackages as $package)
            @php
                $task = $package->task;
                $latestCheck = $package->qcChecks->first();
            @endphp
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <strong>{{ $package->title }}</strong>
                        <span class="badge bg-light text-dark ms-1">{{ \App\Models\ServiceWorkPackage::STATUS_LABELS[$package->status] ?? $package->status }}</span>
                        @if($package->finding)
                            <small class="text-muted d-block">Original Finding: {{ $package->finding->finding_number }} — {{ $package->finding->title }}</small>
                        @endif
                    </div>
                    <div class="small text-end">
                        @if($task)
                        <div>Standard: <strong>{{ $task->standard_minutes }} mnt</strong></div>
                        <div>Actual: <strong>{{ $task->actualMinutes() }} mnt</strong></div>
                        @endif
                    </div>
                </div>

                @if($latestCheck)
                <div class="small border-top pt-2 mt-2">
                    @if($latestCheck->result === 'passed')
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i>QC lolos {{ $latestCheck->checked_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="text-danger"><i class="fas fa-times-circle me-1"></i>QC gagal: {{ $latestCheck->notes }}</span>
                    @endif
                </div>
                @endif

                @if($canPerformQc && in_array($package->status, ['completed', 'qc_failed'], true))
                <form action="{{ route('work-packages.qc.store', $package) }}" method="POST" class="row g-2 border-top pt-2 mt-1">
                    @csrf
                    <div class="col-md-7">
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Catatan QC {{ $package->status === 'qc_failed' ? '(wajib jika FAIL)' : '' }}">
                    </div>
                    <div class="col-md-5 d-flex gap-1 justify-content-end">
                        <button type="submit" name="result" value="passed" class="btn btn-sm btn-success"
                                onclick="return confirm('Lolos QC? Temuan terkait akan ditandai selesai.')">PASS</button>
                        <button type="submit" name="result" value="failed" class="btn btn-sm btn-danger"
                                onclick="return this.form.notes.value.trim() !== '' || (alert('Alasan QC gagal wajib diisi.'), false)">FAIL</button>
                    </div>
                </form>
                @endif
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="fas fa-clipboard-check fa-2x mb-2"></i>
                <p class="mb-0">Belum ada pekerjaan yang menunggu QC.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
