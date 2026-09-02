{{-- Tab: TEMUAN — technical findings between checklist and estimate --}}
@php
    $findings = $service->findings->sortByDesc('id');
    $canUpdate = auth()->user()?->can('findings.update');
    $canResolve = auth()->user()?->can('findings.resolve');
    $canCreatePackage = auth()->user()?->can('work-packages.create');
    $severityBadges = \App\Models\ServiceFinding::SEVERITY_BADGES;
@endphp
<div class="tab-pane fade" id="tab-findings">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h6 class="mb-0"><i class="fas fa-magnifying-glass me-2 text-warning"></i>Temuan Pemeriksaan</h6>
                <a href="{{ route('services.show', $service) }}#tab-checklist" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-clipboard-check me-1"></i> Buka Checklist
                </a>
            </div>

            @forelse($findings as $finding)
            <div class="border rounded p-3 mb-3 {{ $finding->severity === \App\Models\ServiceFinding::SEVERITY_CRITICAL ? 'border-danger' : '' }}">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <strong>{{ $finding->finding_number }}</strong>
                        <span class="badge bg-{{ $finding->severity === 'critical' ? 'danger' : ($finding->severity === 'repair_required' ? 'warning' : 'warning bg-opacity-50 text-dark') }} ms-1">
                            {{ $severityBadges[$finding->severity] ?? '' }} {{ \App\Models\ServiceFinding::SEVERITY_LABELS[$finding->severity] ?? $finding->severity }}
                        </span>
                        <span class="badge bg-light text-dark ms-1">{{ \App\Models\ServiceFinding::STATUS_LABELS[$finding->status] ?? $finding->status }}</span>
                        <h6 class="mt-2 mb-1 text-uppercase">{{ $finding->title }}</h6>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        @if($canCreatePackage && $finding->isActive())
                        <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#wpModal"
                                data-finding-id="{{ $finding->id }}" data-finding-title="{{ $finding->title }}"
                                data-severity="{{ $finding->severity }}"
                                data-measurement="{{ $finding->measurement_value !== null ? $finding->measurement_value.($finding->measurement_unit ? ' '.$finding->measurement_unit : '') : '' }}">
                            <i class="fas fa-briefcase me-1"></i> Buat Work Package
                        </button>
                        @endif
                        @if($canResolve && $finding->isActive())
                        <form action="{{ route('findings.defer', [$service, $finding]) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" title="Tandai Deferred" onclick="return confirm('Tunda temuan ini?')">Tunda</button>
                        </form>
                        <form action="{{ route('findings.resolve', $finding) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-success" title="Resolve" onclick="return confirm('Tandai temuan ini selesai?')">Resolve</button>
                        </form>
                        @endif
                    </div>
                </div>

                <div class="row g-2 mt-1 small">
                    <div class="col-md-6">
                        @if($finding->description)<div><span class="text-muted">Temuan:</span> {{ $finding->description }}</div>@endif
                        @if($finding->measurement_value !== null)
                        <div><span class="text-muted">Measurement:</span>
                            <strong>{{ rtrim(rtrim(number_format((float) $finding->measurement_value, 3, '.', ''), '0'), '.') }}{{ $finding->measurement_unit ? ' '.$finding->measurement_unit : '' }}</strong>
                        </div>
                        @endif
                        @if($finding->technician_note)<div><span class="text-muted">Catatan Teknisi:</span> {{ $finding->technician_note }}</div>@endif
                    </div>
                    <div class="col-md-6">
                        @if($finding->recommendation)<div><span class="text-muted">Rekomendasi:</span> {{ $finding->recommendation }}</div>@endif
                        @if($finding->resolved_at)<div class="text-success"><i class="fas fa-check me-1"></i>Selesai {{ $finding->resolved_at->format('d/m/Y H:i') }}</div>@endif
                    </div>
                </div>

                @if($canUpdate && $finding->isActive())
                <details class="mt-2">
                    <summary class="small text-primary" style="cursor:pointer"><i class="fas fa-edit me-1"></i>Edit Finding</summary>
                    <form action="{{ route('findings.update', $finding) }}" method="POST" class="row g-2 mt-1">
                        @csrf @method('PUT')
                        <div class="col-md-4">
                            <input type="text" name="title" class="form-control form-control-sm" value="{{ $finding->title }}" required>
                        </div>
                        <div class="col-md-2">
                            <select name="severity" class="form-select form-select-sm">
                                @foreach(\App\Models\ServiceFinding::SEVERITY_LABELS as $sev => $label)
                                    <option value="{{ $sev }}" @selected($finding->severity === $sev)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="description" class="form-control form-control-sm" value="{{ $finding->description }}" placeholder="Deskripsi">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="recommendation" class="form-control form-control-sm" value="{{ $finding->recommendation }}" placeholder="Rekomendasi">
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-sm btn-primary w-100"><i class="fas fa-save"></i></button>
                        </div>
                    </form>
                </details>
                @endif
            </div>
            @empty
            <div class="text-center py-4 text-muted">
                <i class="fas fa-magnifying-glass fa-2x mb-2"></i>
                <p class="mb-0">Belum ada temuan. Isi checklist dengan kondisi selain OK untuk memunculkan temuan otomatis.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
