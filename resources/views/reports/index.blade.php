@extends('layouts.app')

@section('title', 'Reports - {{ config('app.name') }}')

@section('content')
<h4 class="mb-3">Reports Dashboard</h4>

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

<div class="row">
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.service') }}" class="text-decoration-none">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-tools display-4"></i>
                    <h5>Service Report</h5>
                    <p class="small mb-0">View service analytics & breakdown</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.sales') }}" class="text-decoration-none">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="bi bi-cart-check display-4"></i>
                    <h5>Sales Report</h5>
                    <p class="small mb-0">View sales analytics & revenue</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.stock') }}" class="text-decoration-none">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam display-4"></i>
                    <h5>Stock Report</h5>
                    <p class="small mb-0">Inventory status & low stock alerts</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.financial') }}" class="text-decoration-none">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up-arrow display-4"></i>
                    <h5>Financial Report</h5>
                    <p class="small mb-0">Profit & loss, income vs expense</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
