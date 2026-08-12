@extends('layouts.app')

@section('title')
    Balance Sheet — {{ config('app.name') }}
@stop

@section('content')
<h4 class="mb-3">Balance Sheet (Neraca)</h4>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">As Of Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="{{ route('reports.balance-sheet') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h4 class="text-primary">@money($totalAssets)</h4>
                <p class="text-muted">Total Assets</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h4 class="text-warning">@money($totalLiabilities)</h4>
                <p class="text-muted">Total Liabilities</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h4 class="text-success">@money($totalEquity)</h4>
                <p class="text-muted">Total Equity</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-{{ ($totalAssets - $totalLiabilities - $totalEquity) === 0 ? 'info' : 'danger' }}">
            <div class="card-body text-center">
                <h4 class="text-info">@money($difference)</h4>
                <p class="text-muted">Balance Difference</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Assets vs Liabilities vs Equity</strong></div>
            <div class="card-body">
                <canvas id="bsChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Asset Breakdown</strong></div>
            <div class="card-body">
                <canvas id="assetChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3 gap-2 no-print">
    <a href="{{ route('reports.export-pdf', ['type' => 'balance-sheet'] + request()->all()) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf"></i> Export PDF</a>
    <a href="{{ route('reports.export-excel', ['type' => 'balance-sheet'] + request()->all()) }}" class="btn btn-success btn-sm"><i class="bi bi-file-excel"></i> Export Excel</a>
</div>

<div class="card mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr><th colspan="2" class="fw-bold fs-5">Statement of Financial Position</th></tr>
                </thead>
                <tbody>
                    <tr class="table-primary">
                        <td colspan="2" class="fw-bold">ASSETS</td>
                    </tr>
                    @forelse($assetAccounts as $aa)
                    <tr>
                        <td class="ps-4">{{ $aa->code }} — {{ $aa->name }}</td>
                        <td class="text-end">@money($aa->balance)</td>
                    </tr>
                    @empty
                    <tr><td class="ps-4 text-muted">No asset accounts</td><td></td></tr>
                    @endforelse
                    <tr class="fw-bold">
                        <td class="ps-4">Total Assets</td>
                        <td class="text-end text-primary">@money($totalAssets)</td>
                    </tr>

                    <tr class="table-warning">
                        <td colspan="2" class="fw-bold">LIABILITIES</td>
                    </tr>
                    @forelse($liabilityAccounts as $la)
                    <tr>
                        <td class="ps-4">{{ $la->code }} — {{ $la->name }}</td>
                        <td class="text-end">@money($la->balance)</td>
                    </tr>
                    @empty
                    <tr><td class="ps-4 text-muted">No liability accounts</td><td></td></tr>
                    @endforelse
                    <tr class="fw-bold">
                        <td class="ps-4">Total Liabilities</td>
                        <td class="text-end text-warning">@money($totalLiabilities)</td>
                    </tr>

                    <tr class="table-success">
                        <td colspan="2" class="fw-bold">EQUITY</td>
                    </tr>
                    @forelse($equityAccounts as $ea)
                    <tr>
                        <td class="ps-4">{{ $ea->code }} — {{ $ea->name }}</td>
                        <td class="text-end">@money($ea->balance)</td>
                    </tr>
                    @empty
                    <tr><td class="ps-4 text-muted">No equity accounts</td><td></td></tr>
                    @endforelse
                    @if($netProfit != 0)
                    <tr>
                        <td class="ps-4">Current Period {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</td>
                        <td class="text-end {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">@money($netProfit)</td>
                    </tr>
                    @endif
                    <tr class="fw-bold">
                        <td class="ps-4">Total Equity</td>
                        <td class="text-end text-success">@money($totalEquity + $netProfit)</td>
                    </tr>

                    <tr class="fw-bold fs-6">
                        <td>Total Liabilities + Equity</td>
                        <td class="text-end {{ $balanced ? 'text-success' : 'text-danger' }}">
                            @money($totalLiabilities + $totalEquity + $netProfit)
                            @if(!$balanced) <span class="text-danger ms-2">(Out of balance!</span>) @endif
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
    new Chart(document.getElementById('bsChart'), {
        type: 'bar',
        data: {
            labels: ['Assets', 'Liabilities', 'Equity'],
            datasets: [{
                label: 'Amount',
                data: [{{ $totalAssets }}, {{ $totalLiabilities }}, {{ $totalEquity + $netProfit }}],
                backgroundColor: ['#0d6efd', '#ffc107', '#198754']
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });

    var assetLabels = @json($assetAccounts->pluck('name'));
    var assetData = @json($assetAccounts->pluck('balance'));
    if (assetLabels.length > 0) {
        new Chart(document.getElementById('assetChart'), {
            type: 'doughnut',
            data: {
                labels: assetLabels,
                datasets: [{
                    data: assetData,
                    backgroundColor: ['#0d6efd','#6610f2','#6f42c1','#d63384','#dc3545','#fd7e14','#ffc107','#198754']
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
