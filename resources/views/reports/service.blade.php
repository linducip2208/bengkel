@extends('layouts.app')

@php
    $filters = $filters ?? [];
@endphp

@section('title', 'Service Report - {{ config('app.name') }}')

@section('content')
<h4 class="mb-3">Service Report</h4>

<div class="card mb-3">
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
            <div class="col-md-2">
                <label class="form-label">Technician</label>
                <select name="technician_id" class="form-select">
                    <option value="">All</option>
                    @foreach($technicians ?? [] as $tech)
                        <option value="{{ $tech->id }}" {{ request('technician_id') == $tech->id ? 'selected' : '' }}>{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Pending</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>In Progress</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Done</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('reports.service') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h4>{{ $report['total_services'] ?? 0 }}</h4>
                <p class="text-muted">Total Services</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <h4>@money($report['total_revenue'] ?? 0)</h4>
                <p class="text-muted">Total Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body text-center">
                <h4>@money($report['avg_value'] ?? 0)</h4>
                <p class="text-muted">Average Service Value</p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3 gap-2">
    <a href="{{ route('reports.export-pdf', ['type' => 'service'] + request()->all()) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf"></i> Export PDF</a>
    <a href="{{ route('reports.export-excel', ['type' => 'service'] + request()->all()) }}" class="btn btn-success btn-sm"><i class="bi bi-file-excel"></i> Export Excel</a>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><strong>Service Breakdown by Date</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
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
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header"><strong>By Technician</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Technician</th><th>Count</th><th>Revenue</th></tr></thead>
                    <tbody>
                        @forelse($report['by_technician'] ?? [] as $tech)
                        <tr>
                            <td>{{ $tech['technician_name'] }}</td>
                            <td>{{ $tech['count'] }}</td>
                            <td>@money($tech['revenue'])</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
