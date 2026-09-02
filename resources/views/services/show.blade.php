@extends('layouts.app')

@section('title', 'Detail Servis: ' . $service->job_no)

@section('content')
@php
    $ws = $service->workflow_status ?? 0;
    // Workflow progress strip: Check-in → Inspection → Estimate → Approval → Work → QC → Invoice → Complete
    $flowSteps = [
        ['label' => 'Check-in', 'done' => $ws >= 1],
        ['label' => 'Inspection', 'done' => $ws >= 2],
        ['label' => 'Estimate', 'done' => $service->estimates->where('status', '!=', 'draft')->count() > 0 || $ws >= 3],
        ['label' => 'Approval', 'done' => $ws >= 4],
        ['label' => 'Work', 'done' => $ws >= 7],
        ['label' => 'QC', 'done' => $ws >= 8],
        ['label' => 'Invoice', 'done' => $ws >= 9 || $service->invoice !== null],
        ['label' => 'Complete', 'done' => $ws >= 12],
    ];
    $currentFlowIndex = collect($flowSteps)->search(fn ($s) => ! $s['done']);
    if ($currentFlowIndex === false) { $currentFlowIndex = count($flowSteps) - 1; }
@endphp
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center gap-1 small">
            @foreach($flowSteps as $i => $step)
                <span class="badge rounded-pill {{ $step['done'] ? 'bg-success' : ($i === $currentFlowIndex ? 'bg-warning text-dark' : 'bg-light text-muted') }}">
                    @if($step['done'])<i class="fas fa-check me-1"></i>@endif{{ $step['label'] }}
                </span>
                @if(! $loop->last)<i class="fas fa-angle-right text-muted"></i>@endif
            @endforeach
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fas fa-clipboard-list text-warning me-2"></i>{{ $service->job_no }}</h5>
    <div>
        @php $ws = $service->workflow_status ?? 0; @endphp
        @if($ws < 12)
        <form action="{{ route('services.advance', $service) }}" method="POST" class="d-inline">
            @csrf
            @php $steps = ['Start','Check In','Inspect','Wait Approval','Approve','In Progress','Wait Parts','QC','Ready','Invoice','Paid','Release']; @endphp
            <button class="btn btn-primary btn-sm" onclick="return confirm('Lanjut ke step berikutnya?')">
                <i class="fas fa-arrow-right me-1"></i> {{ $steps[$ws] ?? 'Advance' }}
            </button>
        </form>
        @else
        <span class="badge bg-success fs-6">Completed</span>
        @endif
        @if($ws < 12)
        <form action="{{ route('services.complete', $service) }}" method="POST" class="d-inline ms-1">
            @csrf
            <button class="btn btn-success btn-sm" onclick="return confirm('Tandai selesai?')">
                <i class="fas fa-check me-1"></i> Force Complete
            </button>
        </form>
        @endif
        <a href="{{ route('services.edit', $service) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <a href="{{ route('services.sendWA', $service) }}" class="btn btn-outline-success btn-sm" target="_blank" title="Kirim estimasi & link approval via WhatsApp">
            <i class="fab fa-whatsapp me-1"></i> Kirim Estimasi WA
        </a>
        <a href="{{ route('reports.service-pdf', $service) }}" class="btn btn-outline-danger btn-sm">
            <i class="fas fa-file-pdf me-1"></i> Laporan
        </a>
        <a href="{{ route('services.sticker', $service) }}" class="btn btn-outline-primary btn-sm" target="_blank">
            <i class="fas fa-sticky-note me-1"></i> Stiker
        </a>
        <a href="{{ route('services.condition-report', $service) }}" class="btn btn-outline-success btn-sm" target="_blank">
            <i class="fas fa-clipboard-check me-1"></i> Kondisi
        </a>
        @if($ws >= 12)
        <button type="button" class="btn btn-outline-info btn-sm" id="surveyBtn">
            <i class="fas fa-star me-1"></i> Kirim Survey
        </button>
        @endif
        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="serviceTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info"><i class="fas fa-info-circle me-1"></i>Info</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-jobcard"><i class="fas fa-id-card me-1"></i>Jobcard</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-checklist"><i class="fas fa-tasks me-1"></i>Checklist</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-findings"><i class="fas fa-magnifying-glass me-1"></i>Temuan @if($service->findings->where('status', '!=', 'resolved')->count())<span class="badge bg-danger ms-1">{{ $service->findings->where('status', '!=', 'resolved')->count() }}</span>@endif</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-estimate"><i class="fas fa-file-signature me-1"></i>Estimasi</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-work"><i class="fas fa-briefcase me-1"></i>Pekerjaan</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-photos"><i class="fas fa-images me-1"></i>Foto</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-checkout"><i class="fas fa-clipboard-check me-1"></i>Checkout</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-qc"><i class="fas fa-award me-1"></i>QC</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-invoice"><i class="fas fa-file-invoice me-1"></i>Invoice</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reservations"><i class="fas fa-boxes me-1"></i>Reservasi Parts</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history"><i class="fas fa-clock-rotate-left me-1"></i>Riwayat</button></li>
</ul>

<div class="tab-content">
    {{-- Tab 1: Service Info --}}
    <div class="tab-pane fade show active" id="tab-info">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title border-bottom pb-2">Informasi Servis</h6>
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted" width="180">Job No</td><td><strong>{{ $service->job_no }}</strong></td></tr>
                    <tr><td class="text-muted">Pelanggan</td><td>{{ $service->customer?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">No. HP</td><td>{{ $service->customer?->phone ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Kendaraan</td><td>
                        {{ $service->vehicle?->number_plate ?? '-' }}
                        - {{ $service->vehicle?->vehicleBrand?->vehicle_brand ?? '' }} {{ $service->vehicle?->model_name ?? '' }}
                    </td></tr>
                    <tr><td class="text-muted">Kategori</td><td>{{ $service->repairCategory?->repair_category_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Service Advisor</td><td>{{ $service->serviceAdvisor?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Judul</td><td>{{ $service->title }}</td></tr>
                    <tr><td class="text-muted">Tanggal</td><td>{{ $service->service_date->format('d M Y H:i') }}</td></tr>
                    <tr><td class="text-muted">Status</td><td>
                        <span class="badge bg-{{ $service->status_color }} bg-opacity-10 text-{{ $service->status_color }} rounded-pill px-3">
                            {{ $service->status_label }}
                        </span>
                        @if($service->is_repeat_job)
                            <span class="badge bg-danger ms-1">⚠️ Repeat Job</span>
                        @endif
                    </td></tr>
                    <tr><td class="text-muted">Durasi</td><td>
                        <span class="fw-bold {{ $service->is_overdue && !$service->completed_at ? 'text-danger' : '' }}">
                            {{ $service->duration_label }}
                        </span>
                        @if($service->is_overdue && !$service->completed_at)
                            <span class="badge bg-danger ms-2">OVERDUE</span>
                        @endif
                        @if($service->started_at && !$service->completed_at)
                            <small class="text-muted ms-2">mulai {{ $service->started_at->format('H:i') }}</small>
                        @endif
                    </td></tr>
                    <tr><td class="text-muted">Biaya</td><td><strong>@include('partials.rupiah', ['amount' => $service->charge])</strong></td></tr>
                    <tr><td class="text-muted">Tagihan</td><td>
                        Invoice <strong>@include('partials.rupiah', ['amount' => $financialSummary['invoiced']])</strong> ·
                        Dibayar <strong class="text-success">@include('partials.rupiah', ['amount' => $financialSummary['paid']])</strong> ·
                        Sisa <strong class="{{ $financialSummary['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">@include('partials.rupiah', ['amount' => $financialSummary['outstanding']])</strong>
                    </td></tr>
                    <tr><td class="text-muted">Teknisi</td><td>
                        @foreach($service->technicians as $tech)
                            <span class="badge bg-light text-dark me-1">{{ $tech->name }}</span>
                        @endforeach
                    </td></tr>
                </table>

                <h6 class="border-bottom pb-2 mt-3"><i class="fas fa-stopwatch me-2"></i>Timer Kerja Teknisi</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Teknisi</th>
                                <th>Mulai</th>
                                <th>Selesai</th>
                                <th class="text-center">Durasi</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($service->serviceTechnicians as $st)
                            <tr>
                                <td>{{ $st->user?->name ?? '-' }}</td>
                                <td>{{ $st->started_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>{{ $st->finished_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="text-center">
                                    @if($st->duration_minutes !== null)
                                        @if($st->finished_at)
                                            <span class="badge bg-success">{{ $st->duration_minutes }} mnt</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ $st->duration_minutes }} mnt (berjalan)</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(!$st->started_at)
                                        <form action="{{ route('service-technicians.start', $st) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success"><i class="fas fa-play me-1"></i> Mulai</button>
                                        </form>
                                    @elseif(!$st->finished_at)
                                        <form action="{{ route('service-technicians.finish', $st) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-stop me-1"></i> Selesai</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada teknisi ditugaskan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($service->description)
                <h6 class="border-bottom pb-2 mt-3">Deskripsi</h6>
                <p>{{ nl2br(e($service->description)) }}</p>
                @endif

                <h6 class="border-bottom pb-2 mt-3">Workflow Timeline</h6>
                <div class="table-responsive">
                <table class="table table-sm table-borderless small">
                    @php
                    $timeline = [
                        ['s'=>0,'l'=>'Booked','t'=>null],
                        ['s'=>1,'l'=>'Checked In','t'=>$service->checked_in_at],
                        ['s'=>2,'l'=>'Inspection','t'=>$service->inspected_at],
                        ['s'=>3,'l'=>'Waiting Approval','t'=>null],
                        ['s'=>4,'l'=>'Approved','t'=>$service->approved_at],
                        ['s'=>5,'l'=>'In Progress','t'=>$service->started_at],
                        ['s'=>6,'l'=>'Waiting Parts','t'=>null],
                        ['s'=>7,'l'=>'QC','t'=>$service->qc_passed_at],
                        ['s'=>8,'l'=>'Ready','t'=>null],
                        ['s'=>9,'l'=>'Invoiced','t'=>$service->invoiced_at],
                        ['s'=>10,'l'=>'Paid','t'=>$service->paid_at],
                        ['s'=>11,'l'=>'Released','t'=>$service->released_at],
                        ['s'=>12,'l'=>'Completed','t'=>$service->completed_at],
                    ];
                    @endphp
                    @foreach($timeline as $step)
                    <tr>
                        <td width="24">
                            @if($ws >= $step['s'])
                                <i class="fas fa-check-circle text-success"></i>
                            @elseif($ws == $step['s']-1)
                                <i class="fas fa-spinner text-warning"></i>
                            @else
                                <i class="far fa-circle text-muted"></i>
                            @endif
                        </td>
                        <td width="140" class="{{ $ws == $step['s'] ? 'fw-bold' : '' }}">{{ $step['l'] }}</td>
                        <td class="text-muted">{{ $step['t'] ? $step['t']->format('d/m/Y H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 2: Jobcard --}}
    <div class="tab-pane fade" id="tab-jobcard">
        <div class="card">
            <div class="card-body">
                @if($service->jobcardDetail)
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Jobcard</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('jobcards.show', $service) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i> Lihat Jobcard
                        </a>
                        <a href="{{ route('jobcards.print', $service) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-print me-1"></i> Print
                        </a>
                    </div>
                </div>
                <form action="{{ route('jobcards.update', $service) }}" method="POST">
                    @csrf @method('PUT')
                @else
                <h6 class="mb-0">Buat Jobcard</h6>
                <form action="{{ route('jobcards.store', $service) }}" method="POST">
                    @csrf
                @endif
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Odometer Masuk <span class="text-danger">*</span></label>
                            <input type="number" name="odometer_in" class="form-control" required
                                   value="{{ old('odometer_in', $service->jobcardDetail?->odometer_in ?? '') }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Masuk <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="in_date" class="form-control" required
                                   value="{{ old('in_date', optional($service->jobcardDetail?->in_date)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Odometer Keluar</label>
                            <input type="number" name="odometer_out" class="form-control"
                                   value="{{ old('odometer_out', $service->jobcardDetail?->odometer_out ?? '') }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Keluar</label>
                            <input type="datetime-local" name="out_date" class="form-control"
                                   value="{{ old('out_date', optional($service->jobcardDetail?->out_date)->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <h6 class="border-bottom pb-2">Rekomendasi Servis Berikutnya</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Servis Berikutnya</label>
                            <input type="date" name="next_service_date" class="form-control"
                                   value="{{ old('next_service_date', $service->jobcardDetail?->next_service_date ?? ($nextService['next_service_date'] ?? '')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">KM Servis Berikutnya</label>
                            <input type="number" name="next_service_km" class="form-control"
                                   value="{{ old('next_service_km', $service->jobcardDetail?->next_service_km ?? ($nextService['next_service_km'] ?? '')) }}" min="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save me-1"></i> Simpan Jobcard
                    </button>
                </form>

                {{-- Parts Used with Warranty Status --}}
                @if(!empty($partsUsed) && count($partsUsed) > 0)
                <h6 class="border-bottom pb-2 mt-4"><i class="fas fa-shield-alt me-2"></i>Pemakaian Sparepart & Garansi</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Sparepart</th>
                                <th class="text-center">Qty</th>
                                <th>Garansi Produk</th>
                                <th class="text-center">Status Garansi</th>
                                <th>Expired</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partsUsed as $part)
                            <tr>
                                <td>
                                    <small class="text-muted">{{ $part['sku'] }}</small><br>
                                    <strong>{{ $part['product_name'] }}</strong>
                                </td>
                                <td class="text-center">{{ $part['qty'] }}</td>
                                <td>{{ $part['warranty'] ?? '-' }}</td>
                                <td class="text-center">
                                    @if($part['warranty'])
                                        @if($part['is_under_warranty'])
                                            <span class="badge bg-success">Under Warranty</span>
                                        @else
                                            <span class="badge bg-secondary">Expired</span>
                                        @endif
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($part['warranty'])
                                        @if($part['is_under_warranty'])
                                            <small class="text-success">{{ $part['warranty_expiry'] }}</small>
                                        @else
                                            <small class="text-muted">{{ $part['warranty_expiry'] }}</small>
                                        @endif
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tab 3: Checklist --}}
    <div class="tab-pane fade" id="tab-checklist">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Hasil Observasi</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('observations.checklist.print', $service) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-print me-1"></i> Print Checklist
                        </a>
                        <a href="{{ route('observations.checklist', $service) }}" class="btn btn-sm btn-danger">
                            <i class="fas fa-edit me-1"></i> Buka Checklist
                        </a>
                    </div>
                </div>
                @php $grouped = $service->serviceObservationPoints->groupBy(fn($r) => $r->observationPoint?->observationType?->observation_type ?? 'Lainnya'); @endphp
                @forelse($grouped as $type => $results)
                <h6 class="border-bottom pb-1 mt-3">{{ $type }}</h6>
                <table class="table table-sm">
                    <thead><tr><th>Poin</th><th>Status</th><th>Komentar</th></tr></thead>
                    <tbody>
                        @foreach($results as $r)
                        <tr>
                            <td>{{ $r->observationPoint->observation_point ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $r->checked ? 'success' : 'danger' }}">
                                    {{ $r->checked ? 'OK' : 'NG' }}
                                </span>
                            </td>
                            <td>{{ $r->comment ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @empty
                <p class="text-muted text-center py-3">Belum ada data checklist. <a href="{{ route('observations.checklist', $service) }}">Isi sekarang</a>.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Tab 4: Temuan (findings) --}}
    @include('services.tabs.findings')

    {{-- Tab 5: Estimasi --}}
    @include('estimates.tab')

    {{-- Tab 6: Pekerjaan (work packages + tasks) --}}
    @include('services.tabs.work')

    {{-- Tab 7: Photos --}}
    <div class="tab-pane fade" id="tab-photos">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('services.upload-image', $service) }}" method="POST" enctype="multipart/form-data" class="mb-4">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <input type="file" name="image" class="form-control form-control-sm" required accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-select form-select-sm" required>
                                <option value="before">Before (Sebelum)</option>
                                <option value="progress">Progress (Proses)</option>
                                <option value="after">After (Sesudah)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="caption" class="form-control form-control-sm" placeholder="Keterangan...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-danger w-100">
                                <i class="fas fa-upload me-1"></i> Upload
                            </button>
                        </div>
                    </div>
                </form>

                <div class="row g-3">
                    @forelse($service->images as $img)
                    <div class="col-md-3">
                        <div class="card h-100">
                            <img src="{{ asset('storage/' . $img->image_path) }}" class="card-img-top" style="height:180px;object-fit:cover;" alt="{{ $img->caption }}">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted">{{ ucfirst($img->type) }}</small>
                                @if($img->caption)<br><small>{{ $img->caption }}</small>@endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3">Belum ada foto.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 5: Checkout --}}
    <div class="tab-pane fade" id="tab-checkout">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Hasil Checkout</h6>
                    <a href="{{ route('checkouts.index', $service) }}" class="btn btn-sm btn-danger">
                        <i class="fas fa-edit me-1"></i> Edit Checkout
                    </a>
                </div>
                @forelse($service->checkoutResults as $cr)
                <div class="border rounded p-3 mb-2">
                    <strong>{{ $cr->checkoutCategory->name ?? '-' }}</strong>
                    <p class="mb-0 text-muted">{{ $cr->result }}</p>
                    @if($cr->comment)<small class="text-muted">{{ $cr->comment }}</small>@endif
                </div>
                @empty
                <p class="text-muted text-center py-3">Belum ada data checkout.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Tab 6: Invoice --}}
    <div class="tab-pane fade" id="tab-invoice">
        <div class="card">
            <div class="card-body">
                @if($service->invoice)
                <div class="text-center">
                    <i class="fas fa-file-invoice fa-3x text-success mb-3"></i>
                    <h6>Invoice #{{ $service->invoice->invoice_number }}</h6>
                    <p class="text-muted">Tanggal: {{ $service->invoice->invoice_date?->format('d M Y') }}</p>
                    <a href="{{ route('invoices.show', $service->invoice) }}" class="btn btn-outline-primary btn-sm">Lihat Invoice</a>
                    <a href="{{ route('invoices.pdf', $service->invoice) }}" class="btn btn-outline-secondary btn-sm">Download PDF</a>
                </div>
                @else
                <div class="text-center py-3">
                    <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Belum ada invoice untuk servis ini.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    {{-- Tab 7: Reservasi Parts --}}
    <div class="tab-pane fade" id="tab-reservations">
        <div class="card">
            <div class="card-body">
                <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-boxes me-2"></i>Reservasi Sparepart</h6>

                <form action="{{ route('services.reservations.store', $service) }}" method="POST" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Sparepart</label>
                        <select name="product_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Sparepart --</option>
                            @foreach($products as $p)
                            @php $avail = $p->current_stock - ($reservedMap[$p->id] ?? 0); @endphp
                            <option value="{{ $p->id }}">{{ $p->name }} (tersedia: {{ $avail }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input type="number" name="quantity" step="0.01" min="0.01" class="form-control form-control-sm" value="{{ old('quantity') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes') }}" placeholder="Opsional...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-danger w-100">
                            <i class="fas fa-lock me-1"></i> Reserve
                        </button>
                    </div>
                </form>

                @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sparepart</th>
                                <th class="text-center">Qty</th>
                                <th>Direservasi Oleh</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reservations as $r)
                            <tr>
                                <td>
                                    <small class="text-muted">{{ $r->product?->product_no ?? '-' }}</small><br>
                                    <strong>{{ $r->product?->name ?? '-' }}</strong>
                                </td>
                                <td class="text-center">{{ $r->quantity }}</td>
                                <td>{{ $r->reserver?->name ?? '-' }}</td>
                                <td>
                                    @if($r->status === 'reserved')
                                        <span class="badge bg-warning text-dark">Reserved</span>
                                    @elseif($r->status === 'consumed')
                                        <span class="badge bg-success">Consumed</span>
                                    @else
                                        <span class="badge bg-secondary">Released</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $r->notes ?: '-' }}</small></td>
                                <td class="text-center">
                                    @if($r->status === 'reserved')
                                    <form action="{{ route('services.reservations.consume', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('Tandai parts ini dipakai dan kurangi stok?')">
                                        @csrf
                                        <button class="btn btn-sm btn-success" title="Pakai / Konsumsi"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form action="{{ route('services.reservations.release', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('Lepas reservasi ini?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary" title="Lepas"><i class="fas fa-undo"></i></button>
                                    </form>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Belum ada reservasi sparepart.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab 8: Immutable audit history --}}
    <div class="tab-pane fade" id="tab-history">
        <div class="card"><div class="card-body">
            <h6 class="border-bottom pb-2"><i class="fas fa-timeline me-2"></i>Audit Timeline Work Order</h6>
            <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>Waktu</th><th>Aksi</th><th>Deskripsi</th><th>User</th><th>Source</th></tr></thead>
                <tbody>
                @forelse($service->activityLogs->sortByDesc('created_at') as $log)
                    <tr><td class="text-muted">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td><td><span class="badge bg-primary">{{ $log->event }}</span></td><td>{{ $log->description }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td>{{ $log->ip ?? 'internal' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">Belum ada aktivitas tercatat.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div></div>
    </div>

    {{-- Tab 11: QC --}}
    @include('services.tabs.qc')
</div>

{{-- Modal Link Survey --}}
@include('services.tabs.work-package-modal')
@include('services.tabs.qc')
@include('services.tabs.work-package-modal')

<div class="modal fade" id="surveyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-star text-warning me-1"></i>Link Survey NPS</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label small">URL Survey</label>
                <div class="input-group">
                    <input type="text" id="surveyUrl" class="form-control" readonly>
                    <button class="btn btn-outline-secondary" id="copySurveyUrl" title="Salin"><i class="fas fa-copy"></i></button>
                </div>
                <a href="#" id="surveyWaLink" target="_blank" class="btn btn-success w-100 mt-3">
                    <i class="fab fa-whatsapp me-1"></i>Kirim via WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const btn = document.getElementById('surveyBtn');
    if (!btn) return;
    btn.addEventListener('click', async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        btn.disabled = true;
        try {
            const res = await fetch('{{ route("services.survey-link", $service) }}', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
            });
            const data = await res.json();
            document.getElementById('surveyUrl').value = data.url;
            document.getElementById('surveyWaLink').href = data.wa;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('surveyModal')).show();
        } catch (e) {
            alert('Gagal membuat link survey.');
        }
        btn.disabled = false;
    });
    document.getElementById('copySurveyUrl')?.addEventListener('click', () => {
        const el = document.getElementById('surveyUrl');
        el.select();
        navigator.clipboard?.writeText(el.value).then(() => alert('Link disalin!'));
    });
})();
</script>
@endsection
