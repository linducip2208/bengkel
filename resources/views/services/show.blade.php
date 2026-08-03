@extends('layouts.app')

@section('title', 'Detail Servis: ' . $service->job_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fas fa-clipboard-list text-warning me-2"></i>{{ $service->job_no }}</h5>
    <div>
        @if($service->done_status < 2)
        <form action="{{ route('services.complete', $service) }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-success btn-sm" onclick="return confirm('Tandai selesai?')">
                <i class="fas fa-check me-1"></i> Selesai
            </button>
        </form>
        @endif
        <a href="{{ route('services.edit', $service) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
        <a href="{{ route('services.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<ul class="nav nav-tabs mb-4" id="serviceTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info"><i class="fas fa-info-circle me-1"></i>Info</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-jobcard"><i class="fas fa-id-card me-1"></i>Jobcard</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-checklist"><i class="fas fa-tasks me-1"></i>Checklist</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-photos"><i class="fas fa-images me-1"></i>Foto</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-checkout"><i class="fas fa-clipboard-check me-1"></i>Checkout</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-invoice"><i class="fas fa-file-invoice me-1"></i>Invoice</button></li>
</ul>

<div class="tab-content">
    {{-- Tab 1: Service Info --}}
    <div class="tab-pane fade show active" id="tab-info">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title border-bottom pb-2">Informasi Servis</h6>
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted" width="180">Job No</td><td><strong>{{ $service->job_no }}</strong></td></tr>
                    <tr><td class="text-muted">Pelanggan</td><td>{{ $service->customer->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">No. HP</td><td>{{ $service->customer->phone ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Kendaraan</td><td>
                        {{ $service->vehicle->number_plate ?? '-' }}
                        - {{ $service->vehicle->vehicleBrand->vehicle_brand ?? '' }} {{ $service->vehicle->model_name ?? '' }}
                    </td></tr>
                    <tr><td class="text-muted">Kategori</td><td>{{ $service->repairCategory->repair_category_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Judul</td><td>{{ $service->title }}</td></tr>
                    <tr><td class="text-muted">Tanggal</td><td>{{ $service->service_date->format('d M Y H:i') }}</td></tr>
                    <tr><td class="text-muted">Status</td><td>
                        @php
                            $labels = [0 => 'Pending', 1 => 'In Progress', 2 => 'Done'];
                            $colors = [0 => 'secondary', 1 => 'warning', 2 => 'success'];
                        @endphp
                        <span class="badge bg-{{ $colors[$service->done_status] }} bg-opacity-10 text-{{ $colors[$service->done_status] }} rounded-pill px-3">
                            {{ $labels[$service->done_status] }}
                        </span>
                    </td></tr>
                    <tr><td class="text-muted">Biaya</td><td><strong>@include('partials.rupiah', ['amount' => $service->charge])</strong></td></tr>
                    <tr><td class="text-muted">Teknisi</td><td>
                        @foreach($service->technicians as $tech)
                            <span class="badge bg-light text-dark me-1">{{ $tech->name }}</span>
                        @endforeach
                    </td></tr>
                </table>

                @if($service->description)
                <h6 class="border-bottom pb-2 mt-3">Deskripsi</h6>
                <p>{{ nl2br(e($service->description)) }}</p>
                @endif
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
            </div>
        </div>
    </div>

    {{-- Tab 3: Checklist --}}
    <div class="tab-pane fade" id="tab-checklist">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Hasil Observasi</h6>
                    <a href="{{ route('observations.checklist', $service) }}" class="btn btn-sm btn-danger">
                        <i class="fas fa-edit me-1"></i> Edit Checklist
                    </a>
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

    {{-- Tab 4: Photos --}}
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
</div>
@endsection
