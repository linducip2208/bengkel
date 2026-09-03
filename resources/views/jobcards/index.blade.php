@extends('layouts.app')

@section('title', 'Daftar Jobcard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-id-card text-warning me-2"></i>Daftar Jobcard</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>In Progress</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Done</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="Dari">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="Sampai">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Job No</th>
                        <th>Pelanggan</th>
                        <th>Kendaraan</th>
                        <th>Odo Masuk</th>
                        <th>Odo Keluar</th>
                        <th>Tgl Masuk</th>
                        <th>Tgl Keluar</th>
                        <th>Servis Berikutnya</th>
                        <th>Status</th>
                        <th>Tahap Saat Ini</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobcards as $service)
                    <tr>
                        <td><span class="fw-semibold">{{ $service->job_no }}</span></td>
                        <td>{{ $service->customer?->name ?? '-' }}</td>
                        <td>{{ $service->vehicle?->number_plate ?? '-' }}</td>
                        <td>{{ number_format($service->jobcardDetail->odometer_in ?? 0, 0, ',', '.') }}</td>
                        <td>{{ $service->jobcardDetail->odometer_out ? number_format($service->jobcardDetail->odometer_out, 0, ',', '.') : '-' }}</td>
                        <td>{{ $service->jobcardDetail->in_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $service->jobcardDetail->out_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            @if($service->jobcardDetail->next_service_date)
                                {{ $service->jobcardDetail->next_service_date->format('d/m/Y') }}<br>
                                <small class="text-muted">{{ number_format($service->jobcardDetail->next_service_km ?? 0, 0, ',', '.') }} km</small>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $labels = [0 => 'Pending', 1 => 'In Progress', 2 => 'Done'];
                                $colors = [0 => 'secondary', 1 => 'warning', 2 => 'success'];
                            @endphp
                            <span class="badge bg-{{ $colors[$service->done_status] }} bg-opacity-10 text-{{ $colors[$service->done_status] }} rounded-pill px-3">
                                {{ $labels[$service->done_status] }}
                            </span>
                        </td>
                        @php $jobProgress = app(\App\Services\WorkshopProgressService::class)->calculate($service); @endphp
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $jobProgress['steps'][$jobProgress['current_step']]['label'] }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('jobcards.show', $service) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('jobcards.print', $service) }}" class="btn btn-sm btn-outline-secondary" title="Print">
                                <i class="fas fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">Belum ada data jobcard.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $jobcards->links('partials.pagination') }}
        </div>
    </div>
</div>
@endsection
