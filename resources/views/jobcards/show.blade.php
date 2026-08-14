@extends('layouts.app')

@section('title', 'Detail Jobcard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-id-card text-warning me-2"></i>Detail Jobcard: {{ $service->job_no }}</h4>
    <div>
        <a href="{{ route('jobcards.print', $service) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-print me-1"></i> Print
        </a>
        <a href="{{ route('jobcards.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h6 class="border-bottom pb-2">Informasi Jobcard</h6>
                <div class="table-responsive">
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted" width="180">Job No</td><td><strong>{{ $service->job_no }}</strong></td></tr>
                    <tr><td class="text-muted">Pelanggan</td><td>{{ $service->customer->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Kendaraan</td><td>{{ $service->vehicle->number_plate ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Kategori</td><td>{{ $service->repairCategory->repair_category_name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Judul</td><td>{{ $service->title }}</td></tr>
                    <tr><td class="text-muted">Tanggal Servis</td><td>{{ $service->service_date->format('d M Y H:i') }}</td></tr>
                </table>
                </div>

                @if($service->jobcardDetail)
                <h6 class="border-bottom pb-2 mt-3">Detail Jobcard</h6>
                <div class="table-responsive">
                <table class="table table-borderless table-sm">
                    <tr><td class="text-muted" width="180">Odometer Masuk</td><td>{{ number_format($service->jobcardDetail->odometer_in, 0, ',', '.') }} km</td></tr>
                    <tr><td class="text-muted">Tanggal Masuk</td><td>{{ $service->jobcardDetail->in_date?->format('d M Y H:i') }}</td></tr>
                    @if($service->jobcardDetail->odometer_out)
                    <tr><td class="text-muted">Odometer Keluar</td><td>{{ number_format($service->jobcardDetail->odometer_out, 0, ',', '.') }} km</td></tr>
                    @endif
                    @if($service->jobcardDetail->out_date)
                    <tr><td class="text-muted">Tanggal Keluar</td><td>{{ $service->jobcardDetail->out_date?->format('d M Y H:i') }}</td></tr>
                    @endif
                </table>
                </div>

                @if($nextService['next_service_date'] || $nextService['next_service_km'])
                <h6 class="border-bottom pb-2 mt-3">Rekomendasi Servis Berikutnya</h6>
                <div class="table-responsive">
                <table class="table table-borderless table-sm">
                    @if($nextService['next_service_date'])
                    <tr><td class="text-muted" width="180">Tanggal</td><td>{{ \Carbon\Carbon::parse($nextService['next_service_date'])->format('d M Y') }}</td></tr>
                    @endif
                    @if($nextService['next_service_km'])
                    <tr><td class="text-muted">KM</td><td>{{ number_format($nextService['next_service_km'], 0, ',', '.') }} km</td></tr>
                    @endif
                </table>
                </div>
                @endif
                @endif

                @if($service->technicians->count())
                <h6 class="border-bottom pb-2 mt-3">Teknisi</h6>
                <p>
                    @foreach($service->technicians as $tech)
                        <span class="badge bg-light text-dark me-1">{{ $tech->name }}</span>
                    @endforeach
                </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
