@extends('layouts.app')

@section('title', 'Detail Kendaraan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-car me-2"></i>{{ $vehicle->number_plate }}</h4>
    <div>
        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="fas fa-tools fa-2x text-primary mb-2"></i>
                <h5 class="mb-0">{{ $serviceHistory->count() }}</h5>
                <small class="text-muted">Total Servis</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="fas fa-clipboard-list fa-2x text-success mb-2"></i>
                <h5 class="mb-0">{{ $jobCards->count() }}</h5>
                <small class="text-muted">Job Card</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice fa-2x text-info mb-2"></i>
                <h5 class="mb-0">{{ $invoices->count() }}</h5>
                <small class="text-muted">Invoice</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="fas fa-tachometer-alt fa-2x text-warning mb-2"></i>
                <h5 class="mb-0">{{ $vehicle->odometer ? number_format($vehicle->odometer) . ' km' : '-' }}</h5>
                <small class="text-muted">Odometer</small>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">
            <i class="fas fa-info-circle me-1"></i>Overview
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#service-history" type="button">
            <i class="fas fa-tools me-1"></i>Riwayat Servis
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#job-cards" type="button">
            <i class="fas fa-clipboard-list me-1"></i>Job Card
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invoices" type="button">
            <i class="fas fa-file-invoice me-1"></i>Invoice
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#inspections" type="button">
            <i class="fas fa-check-double me-1"></i>Inspeksi
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="overview">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Kendaraan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr><th class="w-25">No. Plat</th><td><strong>{{ $vehicle->number_plate }}</strong></td></tr>
                                    <tr><th>Pelanggan</th><td>
                                        <a href="{{ route('customers.show', $vehicle->customer) }}">{{ $vehicle->customer->name }}</a>
                                    </td></tr>
                                    <tr><th>Tipe</th><td>{{ $vehicle->vehicleType->name ?? '-' }}</td></tr>
                                    <tr><th>Merek</th><td>{{ $vehicle->vehicleBrand->name ?? '-' }}</td></tr>
                                    <tr><th>Model</th><td>{{ $vehicle->model_name ?? '-' }}</td></tr>
                                    <tr><th>Tahun</th><td>{{ $vehicle->year ?? '-' }}</td></tr>
                                    <tr><th>Warna</th><td>{{ $vehicle->color ?? '-' }}</td></tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr><th class="w-25">Bahan Bakar</th><td>{{ $vehicle->fuelType->name ?? '-' }}</td></tr>
                                    <tr><th>KM Saat Ini</th><td>{{ $vehicle->odometer ? number_format($vehicle->odometer) . ' km' : '-' }}</td></tr>
                                    <tr><th>VIN</th><td>{{ $vehicle->vin ?? '-' }}</td></tr>
                                    <tr><th>No. Mesin</th><td>{{ $vehicle->engine_number ?? '-' }}</td></tr>
                                    <tr><th>Catatan</th><td>{{ $vehicle->notes ?? '-' }}</td></tr>
                                    <tr><th>Terdaftar</th><td>{{ $vehicle->created_at->format('d M Y') }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Prediksi Servis Berikutnya</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>{{ $nextService['message'] }}
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6><i class="fas fa-tachometer-alt me-2"></i>Riwayat Odometer</h6>
                        @php
                            $odoData = $vehicle->services()->whereNotNull('jobcardDetail')->with('jobcardDetail')->latest()->limit(20)->get()->reverse();
                            $labels = $odoData->pluck('service_date')->map(fn($d)=>$d->format('d/m'));
                            $values = $odoData->pluck('jobcardDetail.odometer_out');
                        @endphp
                        @if($odoData->count() > 1)
                        <canvas id="odoChart" height="120"></canvas>
                        @else
                        <p class="text-muted">Butuh minimal 2 service untuk chart.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Galeri Foto</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('vehicles.upload-image', $vehicle) }}" method="POST" enctype="multipart/form-data" class="mb-3">
                            @csrf
                            <div class="mb-2">
                                <input type="file" name="image" class="form-control form-control-sm" accept="image/*" required>
                            </div>
                            <div class="mb-2">
                                <input type="text" name="caption" class="form-control form-control-sm" placeholder="Keterangan foto">
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-upload me-1"></i>Unggah
                            </button>
                        </form>

                        <div class="row g-2">
                            @forelse($vehicle->images as $image)
                            <div class="col-6">
                                <div class="card">
                                    <img src="{{ asset('storage/' . $image->image_path) }}" class="card-img-top" alt="{{ $image->caption }}" style="height:120px;object-fit:cover;">
                                    <div class="card-body p-2">
                                        <small class="text-muted">{{ $image->caption ?? 'No caption' }}</small>
                                        <form action="{{ route('vehicles.delete-image', $image) }}" method="POST" class="mt-1"
                                            onsubmit="return confirm('Hapus foto ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger w-100">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="col-12 text-center py-3 text-muted">
                                <i class="fas fa-images fa-2x mb-2 d-block"></i>
                                Belum ada foto.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="service-history">
        <div class="card">
            <div class="card-body p-0">
                @if($serviceHistory->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#Job</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th>Durasi</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($serviceHistory as $service)
                            <tr>
                                <td><strong>{{ $service->job_no }}</strong></td>
                                <td>{{ $service->service_date->format('d M Y') }}</td>
                                <td>{{ $service->repairCategory->repair_category_name ?? 'Umum' }}</td>
                                <td>
                                    <span class="badge bg-{{ $service->status_color }}">{{ $service->status_label }}</span>
                                </td>
                                <td>{{ $service->duration_label }}</td>
                                <td class="text-end">
                                    <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">Belum ada riwayat servis.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="job-cards">
        <div class="card">
            <div class="card-body p-0">
                @if($jobCards->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#Job</th>
                                <th>Tanggal Masuk</th>
                                <th>Keluar</th>
                                <th>Odo In</th>
                                <th>Odo Out</th>
                                <th>Teknisi</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobCards as $service)
                            <tr>
                                <td><strong>{{ $service->job_no }}</strong></td>
                                <td>{{ $service->jobcardDetail->in_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $service->jobcardDetail->out_date?->format('d M Y') ?? '-' }}</td>
                                <td>{{ $service->jobcardDetail->odometer_in ? number_format($service->jobcardDetail->odometer_in) : '-' }}</td>
                                <td>{{ $service->jobcardDetail->odometer_out ? number_format($service->jobcardDetail->odometer_out) : '-' }}</td>
                                <td>{{ $service->technicians->pluck('name')->implode(', ') ?: '-' }}</td>
                                <td><span class="badge bg-{{ $service->status_color }}">{{ $service->status_label }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('services.show', $service) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('jobcards.show', $service) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-clipboard-list"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">Belum ada job card.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="invoices">
        <div class="card">
            <div class="card-body p-0">
                @if($invoices->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#Invoice</th>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                                <td>{{ $invoice->invoice_type ?? 'service' }}</td>
                                <td>@money($invoice->grand_total)</td>
                                <td>
                                    @php
                                        $invStatusColors = ['full_paid' => 'success', 'half_paid' => 'warning', 'unpaid' => 'danger'];
                                    @endphp
                                    <span class="badge bg-{{ $invStatusColors[$invoice->status] ?? 'secondary' }}">
                                        {{ $invoice->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">Belum ada invoice.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="inspections">
        <div class="card">
            <div class="card-body p-0">
                @if($inspectionHistory->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#Service</th>
                                <th>Tanggal</th>
                                <th>Kategori Checkout</th>
                                <th>Hasil</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inspectionHistory as $service)
                                @foreach($service->checkoutResults as $result)
                                <tr>
                                    <td><strong>{{ $service->job_no }}</strong></td>
                                    <td>{{ $service->service_date->format('d M Y') }}</td>
                                    <td>{{ $result->checkoutCategory->category_name ?? '-' }}</td>
                                    <td>
                                        @php
                                            $resColors = ['pass' => 'success', 'fail' => 'danger', 'warning' => 'warning'];
                                        @endphp
                                        <span class="badge bg-{{ $resColors[$result->result] ?? 'secondary' }}">
                                            {{ $result->result ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ $result->comment ?? '-' }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted">Belum ada riwayat inspeksi.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($odoData->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>new Chart(document.getElementById('odoChart'),{type:'line',data:{labels:{!! json_encode($labels) !!},datasets:[{label:'Odometer (KM)',data:{!! json_encode($values) !!},borderColor:'#f59e0b',backgroundColor:'rgba(245,158,11,0.1)',fill:true,tension:0.3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:false}}}});</script>
@endif
@endpush
