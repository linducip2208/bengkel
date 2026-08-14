@extends('layouts.app')
@section('title', 'Cash Flow Report — ' . config('app.name'))
@section('content')
<h4 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i>Cash Flow Report</h4>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subDays(30)->toDateString()) }}"></div>
            <div class="col-md-3"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary">Filter</button> <a href="{{ route('reports.cash-flow') }}" class="btn btn-secondary ms-2">Reset</a></div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h4 class="text-success">@money($report['total_income'] ?? 0)</h4><p class="text-muted">Total Kas Masuk</p></div></div></div>
    <div class="col-md-4"><div class="card border-danger"><div class="card-body text-center"><h4 class="text-danger">@money($report['total_expense'] ?? 0)</h4><p class="text-muted">Total Kas Keluar</p></div></div></div>
    <div class="col-md-4"><div class="card border-{{ ($report['net'] ?? 0) >= 0 ? 'primary' : 'danger' }}"><div class="card-body text-center"><h4 class="text-{{ ($report['net'] ?? 0) >= 0 ? 'primary' : 'danger' }}">@money($report['net'] ?? 0)</h4><p class="text-muted">Net Cash Flow</p></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Daily Cash Flow</strong></div>
    <div class="card-body"><canvas id="cashFlowChart" height="80"></canvas></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-striped table-sm mb-0">
            <thead><tr><th>Tanggal</th><th class="text-end">Kas Masuk</th><th class="text-end">Kas Keluar</th><th class="text-end">Net</th></tr></thead>
            <tbody>
                @forelse($report['daily'] ?? [] as $d)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($d['date'])->format('d M Y') }}</td>
                    <td class="text-end text-success">@money($d['income'])</td>
                    <td class="text-end text-danger">@money($d['expense'])</td>
                    <td class="text-end fw-bold {{ $d['net'] >= 0 ? 'text-primary' : 'text-danger' }}">@money($d['net'])</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center py-3">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
var daily = @json($report['daily'] ?? []);
var labels = daily.map(d => d.date);
new Chart(document.getElementById('cashFlowChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {label:'Income',data:daily.map(d => d.income),borderColor:'#198754',backgroundColor:'rgba(25,135,84,0.1)',fill:true,tension:0.3},
            {label:'Expense',data:daily.map(d => d.expense),borderColor:'#dc3545',backgroundColor:'rgba(220,53,69,0.1)',fill:true,tension:0.3}
        ]
    },
    options: {responsive:true, scales:{y:{beginAtZero:true}}}
});
</script>
@endpush
