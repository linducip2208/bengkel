@extends('layouts.app')
@section('title', 'Branch Comparison — ' . config('app.name'))
@section('content')
<h4 class="mb-3"><i class="fas fa-code-branch me-2"></i>Branch Comparison</h4>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}"></div>
            <div class="col-md-3"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Filter</button> <a href="{{ route('reports.branch-comparison') }}" class="btn btn-secondary ms-2">Reset</a></div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4"><div class="card border-primary"><div class="card-body text-center"><h4>@money($report['total_revenue'] ?? 0)</h4><p class="text-muted">Total Revenue</p></div></div></div>
    <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h4>{{ count($report['branches'] ?? []) }}</h4><p class="text-muted">Active Branches</p></div></div></div>
    <div class="col-md-4"><div class="card border-info"><div class="card-body text-center"><h4>{{ ($report['branches'] ?? collect())->sum('service_count') + ($report['branches'] ?? collect())->sum('pos_count') }}</h4><p class="text-muted">Total Transactions</p></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Revenue per Branch</strong></div>
    <div class="card-body"><canvas id="branchChart" height="80"></canvas></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Branch</th><th>Service Count</th><th>Service Revenue</th><th>POS Count</th><th>POS Revenue</th><th class="text-end">Total Revenue</th></tr></thead>
            <tbody>
                @forelse($report['branches'] ?? [] as $b)
                <tr>
                    <td><strong>{{ $b['name'] }}</strong></td>
                    <td>{{ $b['service_count'] }}</td>
                    <td>@money($b['service_revenue'])</td>
                    <td>{{ $b['pos_count'] }}</td>
                    <td>@money($b['pos_revenue'])</td>
                    <td class="text-end fw-bold">@money($b['total_revenue'])</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3">Belum ada data cabang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
var branches = @json($report['branches'] ?? []);
new Chart(document.getElementById('branchChart'), {
    type: 'bar',
    data: {
        labels: branches.map(b => b.name),
        datasets: [
            {label:'Service',data:branches.map(b => b.service_revenue),backgroundColor:'#3b82f6'},
            {label:'POS',data:branches.map(b => b.pos_revenue),backgroundColor:'#10b981'}
        ]
    },
    options: {responsive:true, scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}}}
});
</script>
@endpush
