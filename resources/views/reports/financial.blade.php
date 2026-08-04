@extends('layouts.app')

@section('title', 'Financial Report - Aplikasi Bengkel Terbaik')

@section('content')
<h4 class="mb-3">Financial Report</h4>

<div class="card mb-3">
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
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('reports.financial') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <h4 class="text-success">@money($report['total_income'] ?? 0)</h4>
                <p class="text-muted">Total Income</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4 class="text-danger">@money($report['total_expense'] ?? 0)</h4>
                <p class="text-muted">Total Expenses</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-{{ ($report['profit'] ?? 0) >= 0 ? 'success' : 'danger' }}">
            <div class="card-body text-center">
                <h4 class="text-{{ ($report['profit'] ?? 0) >= 0 ? 'success' : 'danger' }}">@money($report['profit'] ?? 0)</h4>
                <p class="text-muted">{{ ($report['profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Monthly Breakdown Chart</strong></div>
    <div class="card-body">
        <canvas id="financialChart" height="100"></canvas>
    </div>
</div>

<div class="d-flex justify-content-end mb-3 gap-2">
    <a href="{{ route('reports.export-pdf', ['type' => 'financial'] + request()->all()) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf"></i> Export PDF</a>
    <a href="{{ route('reports.export-excel', ['type' => 'financial'] + request()->all()) }}" class="btn btn-success btn-sm"><i class="bi bi-file-excel"></i> Export Excel</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr><th>Month</th><th>Income</th><th>Expense</th><th>Profit/Loss</th></tr>
            </thead>
            <tbody>
                @forelse($report['monthly_breakdown'] ?? [] as $month)
                <tr>
                    <td>{{ $month['month'] }}</td>
                    <td>@money($month['income'])</td>
                    <td>@money($month['expense'])</td>
                    <td class="{{ $month['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        @money($month['profit'])
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var breakdown = @json($report['monthly_breakdown'] ?? []);
    var labels = breakdown.map(function(item) { return item.month; });
    var incomeData = breakdown.map(function(item) { return item.income; });
    var expenseData = breakdown.map(function(item) { return item.expense; });

    new Chart(document.getElementById('financialChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Income', data: incomeData, backgroundColor: '#198754' },
                { label: 'Expense', data: expenseData, backgroundColor: '#dc3545' }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
});
</script>
@endpush
