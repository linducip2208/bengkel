@extends('layouts.app')

@section('title')
Reports - {{ config('app.name') }}
@endsection

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
                    <p class="small mb-0">Service analytics & breakdown</p>
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
                    <p class="small mb-0">Sales analytics & revenue</p>
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
                    <p class="small mb-0">Inventory & low stock alerts</p>
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
                    <p class="small mb-0">P&L, income vs expense</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.technician') }}" class="text-decoration-none">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-person-gear display-4"></i>
                    <h5>Technician Performance</h5>
                    <p class="small mb-0">Productivity & revenue per mekanik</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.work-time') }}" class="text-decoration-none">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="bi bi-stopwatch display-4"></i>
                    <h5>Waktu Kerja</h5>
                    <p class="small mb-0">Standard vs actual per pekerjaan & teknisi</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.customer-lifetime') }}" class="text-decoration-none">
            <div class="card bg-danger text-white" style="background:linear-gradient(135deg,#8b5cf6,#ec4899)!important">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill display-4"></i>
                    <h5>Customer Lifetime</h5>
                    <p class="small mb-0">Top customers & repeat value</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.ar-aging') }}" class="text-decoration-none">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <i class="bi bi-clock-history display-4"></i>
                    <h5>AR Aging</h5>
                    <p class="small mb-0">Outstanding invoices by age</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.parts-usage') }}" class="text-decoration-none">
            <div class="card bg-light text-dark border">
                <div class="card-body text-center">
                    <i class="bi bi-cpu display-4"></i>
                    <h5>Parts Usage</h5>
                    <p class="small mb-0">Spare part consumption</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.branch-comparison') }}" class="text-decoration-none">
            <div class="card text-white" style="background:linear-gradient(135deg,#1e40af,#3b82f6)">
                <div class="card-body text-center">
                    <i class="bi bi-building display-4"></i>
                    <h5>Branch Comparison</h5>
                    <p class="small mb-0">Side-by-side cabang revenue</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 mb-3">
        <a href="{{ route('reports.cash-flow') }}" class="text-decoration-none">
            <div class="card text-white" style="background:linear-gradient(135deg,#059669,#10b981)">
                <div class="card-body text-center">
                    <i class="bi bi-cash-coin display-4"></i>
                    <h5>Cash Flow</h5>
                    <p class="small mb-0">Daily cash in/out summary</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
