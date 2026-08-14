@extends('layouts.app')
@section('title', $subcontractor->name)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-user-gear me-2"></i>{{ $subcontractor->name }}</h4>
    <a href="{{ route('subcontractors.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="card mb-3"><div class="card-body">
            <div class="table-responsive">
            <table class="table table-sm mb-0">
                <tr><td class="text-muted">Telepon</td><td>{{ $subcontractor->phone ?? '-' }}</td></tr>
                <tr><td class="text-muted">Email</td><td>{{ $subcontractor->email ?? '-' }}</td></tr>
                <tr><td class="text-muted">Alamat</td><td>{{ $subcontractor->address ?? '-' }}</td></tr>
                <tr><td class="text-muted">Spesialisasi</td><td>{{ $subcontractor->specialty ?? '-' }}</td></tr>
                @if($subcontractor->notes)<tr><td class="text-muted">Catatan</td><td>{{ $subcontractor->notes }}</td></tr>@endif
            </table>
            </div>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header"><strong>Pekerjaan yang Di-sub</strong></div><div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Service</th><th>Biaya</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($subcontractor->jobs as $job)
                    <tr>
                        <td><a href="{{ route('services.show', $job->service) }}">{{ $job->service->job_no ?? '#' . $job->service_id }}</a></td>
                        <td>@money($job->cost)</td>
                        <td><span class="badge bg-{{ $job->status === 'done' ? 'success' : 'warning' }}">{{ $job->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada pekerjaan.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div></div>
    </div>
</div>
@endsection
