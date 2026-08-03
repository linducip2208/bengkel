@extends('layouts.app')

@section('title', 'Dashboard - Bengkel Paten')

@section('content')
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">{{ $stats['open_services'] }}</h5>
                <p class="card-text small">Open Services</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <h5 class="card-title text-success">{{ $stats['completed_today'] }}</h5>
                <p class="card-text small">Completed Today</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <h5 class="card-title text-info">@money($stats['revenue_today'])</h5>
                <p class="card-text small">Today's Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card border-secondary">
            <div class="card-body text-center">
                <h5 class="card-title">@money($stats['revenue_this_month'])</h5>
                <p class="card-text small">Monthly Revenue</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h5 class="card-title text-warning">{{ $stats['outstanding_invoices'] }}</h5>
                <p class="card-text small">Outstanding Invoices</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h5 class="card-title text-danger">{{ $stats['low_stock_count'] }}</h5>
                <p class="card-text small">Low Stock Items</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex gap-2">
            <a href="{{ route('services.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Service</a>
            <a href="{{ route('customers.create') }}" class="btn btn-success btn-sm"><i class="bi bi-person-plus"></i> New Customer</a>
            <a href="{{ route('invoices.create') }}" class="btn btn-info btn-sm"><i class="bi bi-receipt"></i> New Invoice</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Recent Services</h6>
                <a href="{{ route('services.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Job No</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentServices as $service)
                        <tr>
                            <td><a href="{{ route('services.show', $service) }}">{{ $service->job_no }}</a></td>
                            <td>{{ $service->customer->name ?? '-' }}</td>
                            <td>{{ $service->vehicle->number_plate ?? '-' }}</td>
                            <td>
                                @if($service->done_status == 0)<span class="badge bg-warning">Pending</span>
                                @elseif($service->done_status == 1)<span class="badge bg-info">In Progress</span>
                                @else<span class="badge bg-success">Done</span>@endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center">No recent services</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Upcoming Services (Next 7 Days)</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Job No</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingServices as $service)
                        <tr>
                            <td><a href="{{ route('services.show', $service) }}">{{ $service->job_no }}</a></td>
                            <td>{{ $service->customer->name ?? '-' }}</td>
                            <td>{{ $service->vehicle->number_plate ?? '-' }}</td>
                            <td>{{ $service->service_date ? $service->service_date->format('d/m/Y') : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center">No upcoming services</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
