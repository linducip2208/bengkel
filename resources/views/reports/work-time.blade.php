@extends('layouts.app')

@section('title', 'Laporan Waktu Kerja - '.config('app.name'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="fas fa-stopwatch text-primary me-2"></i>Laporan Waktu Kerja (Standard vs Actual)</h4>
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

{{-- Filters --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-0">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="{{ route('reports.work-time') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Totals --}}
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="card border-start border-primary border-4 h-100"><div class="card-body py-2 px-3">
            <small class="text-muted">Total Standard Time</small>
            <h5 class="mb-0 text-primary">{{ $packageReport['total_standard_minutes'] }} menit</h5>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card border-start border-info border-4 h-100"><div class="card-body py-2 px-3">
            <small class="text-muted">Total Actual Time</small>
            <h5 class="mb-0 text-info">{{ $packageReport['total_actual_minutes'] }} menit</h5>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card border-start {{ $packageReport['total_variance_minutes'] > 0 ? 'border-danger' : 'border-success' }} border-4 h-100"><div class="card-body py-2 px-3">
            <small class="text-muted">Variance</small>
            <h5 class="mb-0 {{ $packageReport['total_variance_minutes'] > 0 ? 'text-danger' : 'text-success' }}">{{ $packageReport['total_variance_minutes'] > 0 ? '+' : '' }}{{ $packageReport['total_variance_minutes'] }} menit</h5>
        </div></div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card border-start border-success border-4 h-100"><div class="card-body py-2 px-3">
            <small class="text-muted">Efisiensi</small>
            <h5 class="mb-0 text-success">{{ $packageReport['efficiency'] !== null ? $packageReport['efficiency'].'%' : '-' }}</h5>
        </div></div>
    </div>
</div>

{{-- Per work package --}}
<div class="card mb-3">
    <div class="card-header"><strong><i class="fas fa-briefcase me-2"></i>Standard vs Actual — Per Work Package</strong></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pekerjaan</th>
                        <th>No Service</th>
                        <th>Sumber</th>
                        <th>Status</th>
                        <th class="text-center">Standard</th>
                        <th class="text-center">Actual</th>
                        <th class="text-center">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packageReport['rows'] as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['title'] }}</td>
                        <td>{{ $row['job_no'] ?? '-' }}</td>
                        <td>{{ $row['finding_number'] ?? 'manual' }}</td>
                        <td><span class="badge bg-light text-dark">{{ $row['status_label'] }}</span></td>
                        <td class="text-center">{{ $row['standard_minutes'] }} mnt</td>
                        <td class="text-center">{{ $row['actual_minutes'] !== null ? $row['actual_minutes'].' mnt' : '—' }}</td>
                        <td class="text-center">
                            @if($row['variance_minutes'] === null)
                                <span class="text-muted">—</span>
                            @elseif($row['variance_minutes'] > 0)
                                <span class="badge bg-danger">+{{ $row['variance_minutes'] }} mnt</span>
                            @elseif($row['variance_minutes'] < 0)
                                <span class="badge bg-success">{{ $row['variance_minutes'] }} mnt</span>
                            @else
                                <span class="badge bg-secondary">0</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada work package.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Per technician --}}
<div class="card">
    <div class="card-header"><strong><i class="fas fa-user-gear me-2"></i>Per Teknisi</strong></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Teknisi</th>
                        <th class="text-center">Total Task</th>
                        <th class="text-center">Selesai (incl. QC)</th>
                        <th class="text-center">Standard Minutes</th>
                        <th class="text-center">Actual Minutes</th>
                        <th class="text-center">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($technicianReport['rows'] as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['technician_name'] }}</td>
                        <td class="text-center">{{ $row['total_tasks'] }}</td>
                        <td class="text-center">{{ $row['completed_tasks'] }}</td>
                        <td class="text-center">{{ $row['standard_minutes'] }} mnt</td>
                        <td class="text-center">{{ $row['actual_minutes'] }} mnt</td>
                        <td class="text-center">
                            @php $v = $row['actual_minutes'] - $row['standard_minutes']; @endphp
                            <span class="badge {{ $v > 0 ? 'bg-danger' : 'bg-success' }}">{{ $v > 0 ? '+' : '' }}{{ $v }} mnt</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada task yang ditugaskan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
