@extends('layouts.app')

@section('title', 'Detail Kendaraan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-car me-2"></i>{{ $vehicle->license_plate }}</h4>
    <div>
        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

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
                            <tr><th class="w-25">No. Plat</th><td><strong>{{ $vehicle->license_plate }}</strong></td></tr>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Prediksi Servis Berikutnya</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>{{ $nextService['message'] }}
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Riwayat Servis</h5>
            </div>
            <div class="card-body p-0">
                @if($serviceHistory->isNotEmpty())
                <div class="list-group list-group-flush">
                    @foreach($serviceHistory as $service)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ $service->created_at->format('d M Y') }}</strong>
                                <span class="text-muted ms-2">{{ $service->repairCategory->repair_category_name ?? 'Umum' }}</span>
                            </div>
                            @php
                                $svcLabels = [0 => 'Pending', 1 => 'In Progress', 2 => 'Done'];
                                $svcColors = [0 => 'secondary', 1 => 'warning', 2 => 'success'];
                            @endphp
                            <span class="badge bg-{{ $svcColors[$service->done_status] ?? 'secondary' }}">
                                {{ $svcLabels[$service->done_status] ?? '-' }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4 text-muted">Belum ada riwayat servis.</div>
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
<div class="card mt-3"><div class="card-body">
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
</div></div>
@push('scripts')
@if($odoData->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>new Chart(document.getElementById('odoChart'),{type:'line',data:{labels:{!! json_encode($labels) !!},datasets:[{label:'Odometer (KM)',data:{!! json_encode($values) !!},borderColor:'#f59e0b',backgroundColor:'rgba(245,158,11,0.1)',fill:true,tension:0.3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:false}}}});</script>
@endif
@endpush
@endsection
