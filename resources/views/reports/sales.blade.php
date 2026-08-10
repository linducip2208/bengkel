@extends('layouts.app')

@section('title')
Sales Report - {{ config('app.name') }}
@endsection

@section('content')
<h4 class="mb-3">Sales Report</h4>

<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->subMonth()->toDateString()) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('reports.sales') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h4>{{ $report['total_sales'] ?? 0 }}</h4>
                <p class="text-muted">Total Sales</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-success">
            <div class="card-body text-center">
                <h4>@money($report['total_revenue'] ?? 0)</h4>
                <p class="text-muted">Total Revenue</p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3 gap-2 no-print">
    <a href="{{ route('reports.export-pdf', ['type' => 'sales'] + request()->all()) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf"></i> Export PDF</a>
    <a href="{{ route('reports.export-excel', ['type' => 'sales'] + request()->all()) }}" class="btn btn-success btn-sm"><i class="bi bi-file-excel"></i> Export Excel</a>
</div>

<div class="card">
    <div class="card-header"><strong>Sales by Date</strong></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead><tr><th>Date</th><th>Count</th><th>Revenue</th></tr></thead>
            <tbody>
                @forelse($report['by_date'] ?? [] as $date => $data)
                <tr>
                    <td>{{ $date }}</td>
                    <td>{{ $data['count'] }}</td>
                    <td>@money($data['revenue'])</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-center">No data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
