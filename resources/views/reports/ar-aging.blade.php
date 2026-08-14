@extends('layouts.app')
@section('title', 'AR Aging Report — ' . config('app.name'))
@section('content')
<h4 class="mb-3"><i class="fas fa-clock me-2"></i>AR Aging Report</h4>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4>{{ ($report['aging']['90+']['total'] ?? 0) + ($report['aging']['61-90']['total'] ?? 0) > 0 ? ($report['aging']['90+']['total'] ?? 0) + ($report['aging']['61-90']['total'] ?? 0) : 0 }}</h4>
                <p class="text-muted">Overdue > 60 Hari</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h4>{{ ($report['aging']['31-60']['total'] ?? 0) + ($report['aging']['1-30']['total'] ?? 0) }}</h4>
                <p class="text-muted">Overdue 1-60 Hari</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h4>{{ $report['aging']['current']['count'] ?? 0 }}</h4>
                <p class="text-muted">Belum Jatuh Tempo</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h4>{{ count($report['invoices'] ?? []) }}</h4>
                <p class="text-muted">Total Outstanding</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>AR Aging Chart</strong></div>
    <div class="card-body"><canvas id="arChart" height="80"></canvas></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-striped mb-0 table-sm">
            <thead class="table-light">
                <tr><th>Invoice</th><th>Customer</th><th>Tgl Invoice</th><th>Jatuh Tempo</th><th>Overdue</th><th class="text-end">Sisa</th><th>Age Group</th></tr>
            </thead>
            <tbody>
                @forelse($report['invoices'] ?? [] as $inv)
                <tr class="{{ $inv->days_overdue > 60 ? 'table-danger' : ($inv->days_overdue > 30 ? 'table-warning' : '') }}">
                    <td><a href="{{ route('invoices.show', $inv) }}"><code>{{ $inv->invoice_number }}</code></a></td>
                    <td>{{ $inv->customer?->name ?? '-' }}</td>
                    <td>{{ $inv->invoice_date->format('d/m/y') }}</td>
                    <td>{{ $inv->due_date?->format('d/m/y') ?? '-' }}</td>
                    <td>{{ $inv->days_overdue }} hari</td>
                    <td class="text-end fw-bold">@money($inv->remaining)</td>
                    <td><span class="badge bg-{{ $inv->age_group === '90+' ? 'danger' : ($inv->age_group === 'current' ? 'success' : 'warning') }}">{{ $inv->age_group }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-3">Semua invoice sudah lunas.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
var aging = @json($report['aging'] ?? []);
var labels = ['Current', '1-30', '31-60', '61-90', '90+'];
var totals = [
    (aging['current']?.total || 0),
    (aging['1-30']?.total || 0),
    (aging['31-60']?.total || 0),
    (aging['61-90']?.total || 0),
    (aging['90+']?.total || 0)
];
new Chart(document.getElementById('arChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{label:'Outstanding (Rp)',data:totals,backgroundColor:['#198754','#ffc107','#fd7e14','#dc3545','#6f42c1']}]
    },
    options: {responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}
});
</script>
@endpush
