@extends('layouts.app')
@section('title', 'Parts Usage Report — ' . config('app.name'))
@section('content')
<h4 class="mb-3"><i class="fas fa-microchip me-2"></i>Parts Usage Report</h4>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subMonth()->toDateString()) }}"></div>
            <div class="col-md-3"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Filter</button> <a href="{{ route('reports.parts-usage') }}" class="btn btn-secondary ms-2">Reset</a></div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4"><div class="card border-info"><div class="card-body text-center"><h4>{{ count($report['usages'] ?? []) }}</h4><p class="text-muted">Parts Digunakan</p></div></div></div>
    <div class="col-md-4"><div class="card border-warning"><div class="card-body text-center"><h4>{{ ($report['usages'] ?? collect())->sum('total_qty') }}</h4><p class="text-muted">Total Qty</p></div></div></div>
    <div class="col-md-4"><div class="card border-danger"><div class="card-body text-center"><h4>@money($report['total_cost'] ?? 0)</h4><p class="text-muted">Total Cost</p></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Top Parts Usage</strong></div>
    <div class="card-body"><canvas id="partsChart" height="80"></canvas></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>#</th><th>Product</th><th>Category</th><th>Qty Used</th><th>Unit Cost</th><th class="text-end">Total Cost</th></tr></thead>
            <tbody>
                @php $no = 1 @endphp
                @forelse($report['usages'] ?? [] as $u)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $u->product_name }}</td>
                    <td>{{ $u->category }}</td>
                    <td>{{ $u->total_qty }}</td>
                    <td>@money($u->unit_cost)</td>
                    <td class="text-end fw-bold">@money($u->total_cost)</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3">Belum ada pemakaian parts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>
var usages = @json($report['usages'] ?? []);
var top10 = usages.slice(0, 10);
new Chart(document.getElementById('partsChart'), {
    type: 'bar',
    data: {
        labels: top10.map(u => u.product_name),
        datasets: [{label:'Total Qty',data:top10.map(u => u.total_qty),backgroundColor:'#3b82f6'}]
    },
    options: {responsive:true, indexAxis:'y', plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true}}}
});
</script>
@endpush
