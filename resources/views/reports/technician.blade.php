@extends('layouts.app')
@section('title', 'Produktivitas Teknisi')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endpush
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-user-gear me-2"></i>Produktivitas Teknisi</h4>
</div>
<form method="GET" class="row g-2 mb-3 no-print">
    <div class="col-md-3"><input type="date" name="start_date" class="form-control" value="{{ $start }}"></div>
    <div class="col-md-3"><input type="date" name="end_date" class="form-control" value="{{ $end }}"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Filter</button></div>
</form>

<div class="row mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h5>{{ $totalJobs }}</h5><small>Total Job</small></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h5>{{ $technicians->count() }}</h5><small>Teknisi Aktif</small></div></div></div>
    @if($topTechnician)<div class="col-md-3"><div class="card border-warning"><div class="card-body text-center"><h5>{{ $topTechnician->technician_name }}</h5><small class="text-warning">Top Performer</small></div></div></div>@endif
</div>

<div class="row mb-3">
    <div class="col-md-7">
        <div class="card"><div class="card-header">Revenue per Teknisi</div><div class="card-body"><canvas id="revChart" height="200"></canvas></div></div>
    </div>
    <div class="col-md-5">
        <div class="card"><div class="card-header">Job per Teknisi</div><div class="card-body"><canvas id="jobChart" height="200"></canvas></div></div>
    </div>
</div>

<div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>Teknisi</th><th class="text-center">Job</th><th class="text-end">Revenue</th><th class="text-end">Avg Durasi</th><th class="text-end">Avg/Job</th></tr></thead>
    <tbody>
        @forelse($technicians as $t)
        <tr>
            <td><strong>{{ $t->technician_name }}</strong></td>
            <td class="text-center">{{ $t->job_count }}</td>
            <td class="text-end">@money($t->total_revenue)</td>
            <td class="text-end">{{ $t->avg_duration ? $t->avg_duration . ' jam' : '-' }}</td>
            <td class="text-end">@money($t->job_count > 0 ? $t->total_revenue / $t->job_count : 0)</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada data.</td></tr>
        @endforelse
    </tbody>
</table>
</div></div>
@push('scripts')
<script>
new Chart(document.getElementById('revChart'),{type:'bar',data:{labels:{!! json_encode($technicians->pluck('technician_name')) !!},datasets:[{label:'Revenue',data:{!! json_encode($technicians->pluck('total_revenue')) !!},backgroundColor:'rgba(37,99,235,0.7)',borderRadius:6}]},options:{responsive:true,scales:{y:{ticks:{callback:v=>'Rp '+v.toLocaleString('id-ID')}}}}});
new Chart(document.getElementById('jobChart'),{type:'doughnut',data:{labels:{!! json_encode($technicians->pluck('technician_name')) !!},datasets:[{data:{!! json_encode($technicians->pluck('job_count')) !!},backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16']}]}});
</script>
@endpush
@endsection
