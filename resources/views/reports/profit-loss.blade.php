@extends('layouts.app')

@section('title')
    Profit & Loss — {{ config('app.name') }}
@stop

@section('content')
<h4 class="mb-3">Profit & Loss (Laba Rugi)</h4>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->startOfYear()->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="{{ route('reports.profit-loss') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h4 class="text-success">@money($totalRevenue)</h4>
                <p class="text-muted">Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h4 class="text-warning">@money($totalCogs)</h4>
                <p class="text-muted">Cost of Goods Sold</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4 class="text-danger">@money($totalExpenses)</h4>
                <p class="text-muted">Total Expenses</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-{{ $netProfit >= 0 ? 'primary' : 'danger' }}">
            <div class="card-body text-center">
                <h4 class="text-{{ $netProfit >= 0 ? 'primary' : 'danger' }}">@money($netProfit)</h4>
                <p class="text-muted">{{ $netProfit >= 0 ? 'Net Profit' : 'Net Loss' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Revenue vs Expenses Chart</strong></div>
            <div class="card-body">
                <canvas id="plChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Expense Breakdown</strong></div>
            <div class="card-body">
                <canvas id="expenseChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3 gap-2 no-print">
    <a href="{{ route('reports.export-pdf', ['type' => 'profit-loss'] + request()->all()) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf"></i> Export PDF</a>
    <a href="{{ route('reports.export-excel', ['type' => 'profit-loss'] + request()->all()) }}" class="btn btn-success btn-sm"><i class="bi bi-file-excel"></i> Export Excel</a>
</div>

<div class="card mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr><th colspan="2" class="fw-bold fs-5">Income Statement</th></tr>
                </thead>
                <tbody>
                    <tr class="table-success">
                        <td colspan="2" class="fw-bold">Revenue</td>
                    </tr>
                    @forelse($revenueAccounts as $ra)
                    <tr>
                        <td class="ps-4">{{ $ra->name }}</td>
                        <td class="text-end">@money($ra->balance)</td>
                    </tr>
                    @empty
                    <tr><td class="ps-4 text-muted">No revenue data</td><td></td></tr>
                    @endforelse
                    <tr class="fw-bold">
                        <td class="ps-4">Total Revenue</td>
                        <td class="text-end text-success">@money($totalRevenue)</td>
                    </tr>

                    <tr class="table-warning">
                        <td colspan="2" class="fw-bold">Cost of Goods Sold</td>
                    </tr>
                    @forelse($cogsAccounts as $ca)
                    <tr>
                        <td class="ps-4">{{ $ca->name }}</td>
                        <td class="text-end">(@money($ca->balance))</td>
                    </tr>
                    @empty
                    <tr><td class="ps-4 text-muted">No COGS data</td><td></td></tr>
                    @endforelse
                    <tr class="fw-bold">
                        <td class="ps-4">Gross Profit</td>
                        <td class="text-end text-primary">@money($grossProfit)</td>
                    </tr>

                    <tr class="table-danger">
                        <td colspan="2" class="fw-bold">Operating Expenses</td>
                    </tr>
                    @forelse($expenseAccounts as $ea)
                    <tr>
                        <td class="ps-4">{{ $ea->name }}</td>
                        <td class="text-end">(@money($ea->balance))</td>
                    </tr>
                    @empty
                    <tr><td class="ps-4 text-muted">No expense data</td><td></td></tr>
                    @endforelse

                    <tr class="fw-bold fs-5">
                        <td>Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</td>
                        <td class="text-end {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                            @money($netProfit)
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    new Chart(document.getElementById('plChart'), {
        type: 'bar',
        data: {
            labels: ['Revenue', 'COGS', 'Expenses', 'Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}'],
            datasets: [{
                label: 'Amount',
                data: [{{ $totalRevenue }}, {{ $totalCogs }}, {{ $totalExpenses }}, {{ abs($netProfit) }}],
                backgroundColor: ['#198754', '#ffc107', '#dc3545', '{{ $netProfit >= 0 ? '#0d6efd' : '#dc3545' }}']
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });

    var expenseLabels = @json($expenseAccounts->pluck('name'));
    var expenseData = @json($expenseAccounts->pluck('balance'));
    if (expenseLabels.length > 0) {
        new Chart(document.getElementById('expenseChart'), {
            type: 'doughnut',
            data: {
                labels: expenseLabels,
                datasets: [{
                    data: expenseData,
                    backgroundColor: ['#dc3545','#fd7e14','#ffc107','#6f42c1','#20c997','#0dcaf0','#6c757d','#198754']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
@endpush
