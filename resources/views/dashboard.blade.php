@extends('layouts.app')

@section('title')
Dashboard - {{ config('app.name') }}
@endsection

@push('scripts')
<script>
// Auto-refresh dashboard every 60 seconds
setInterval(() => { fetch(window.location.href).then(r=>r.text()).then(html=>{
    const parser = new DOMParser(); const doc = parser.parseFromString(html,'text/html');
    document.querySelector('.row.mb-3').innerHTML = doc.querySelector('.row.mb-3').innerHTML;
})}, 60000);
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
@endpush

@section('content')
{{-- Low Stock Alert — sekali per session --}}
@if($lowStockAlert)
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
    <div>
        <strong>Perhatian!</strong> Ada <strong>{{ $stats['low_stock_count'] }}</strong> produk dengan stok menipis.
        <a href="{{ route('reports.stock') }}" class="alert-link">Lihat laporan stok</a>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Role Widgets --}}
@if(!empty($roleWidgets))
<div class="row mb-3">
    @isset($roleWidgets['revenue'])
    <div class="col-md-3 mb-2">
        <div class="card border-left-success" style="border-left:4px solid #10b981;">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Pemasukan Bulan Ini</small>
                <h5 class="mb-0 text-success">@money($roleWidgets['revenue'])</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-left-danger" style="border-left:4px solid #ef4444;">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Pengeluaran Bulan Ini</small>
                <h5 class="mb-0 text-danger">@money($roleWidgets['expense'])</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-left-primary" style="border-left:4px solid #3b82f6;">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Profit Bulan Ini</small>
                <h5 class="mb-0 text-primary">@money($roleWidgets['profit'])</h5>
            </div>
        </div>
    </div>
    @endisset
    <div class="col-md-3 mb-2">
        <div class="card border-left-warning" style="border-left:4px solid #f59e0b;">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Task Saya</small>
                <h5 class="mb-0 text-warning">{{ $roleWidgets['my_tasks'] }} service</h5>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Stats Cards --}}
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

{{-- Charts Row --}}
<div class="row mb-4">
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Revenue & Expenses (14 Hari)</h6></div>
            <div class="card-body">
                <canvas id="revenueChart" height="260"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status Service Hari Ini</h6></div>
            <div class="card-body">
                <canvas id="statusChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex gap-2">
            <a href="{{ route('services.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> New Service</a>
            <a href="{{ route('customers.create') }}" class="btn btn-success btn-sm"><i class="bi bi-person-plus"></i> New Customer</a>
            <a href="{{ route('invoices.create') }}" class="btn btn-info btn-sm"><i class="bi bi-receipt"></i> New Invoice</a>
        </div>
    </div>
</div>

{{-- Tables --}}
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
                        <tr><th>Job No</th><th>Customer</th><th>Vehicle</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($recentServices as $service)
                        <tr>
                            <td><a href="{{ route('services.show', $service) }}">{{ $service->job_no }}</a></td>
                            <td>{{ $service->customer->name ?? '-' }}</td>
                            <td>{{ $service->vehicle->number_plate ?? '-' }}</td>
                            <td>@if($service->done_status == 0)<span class="badge bg-warning">Pending</span>@elseif($service->done_status == 1)<span class="badge bg-info">In Progress</span>@else<span class="badge bg-success">Done</span>@endif</td>
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
            <div class="card-header"><h6 class="mb-0">Upcoming Services (Next 7 Days)</h6></div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr><th>Job No</th><th>Customer</th><th>Vehicle</th><th>Date</th></tr>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const ctx1 = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: {!! json_encode($chartData['days']) !!},
            datasets: [
                {
                    label: 'Pemasukan',
                    data: {!! json_encode($chartData['revenue']) !!},
                    backgroundColor: 'rgba(16,185,129,0.7)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 6,
                },
                {
                    label: 'Pengeluaran',
                    data: {!! json_encode($chartData['expenses']) !!},
                    backgroundColor: 'rgba(239,68,68,0.6)',
                    borderColor: '#ef4444',
                    borderWidth: 1,
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } }
            }
        }
    });

    // Status Pie Chart
    const ctx2 = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Done Today'],
            datasets: [{
                data: [{{ $statusChart['pending'] }}, {{ $statusChart['in_progress'] }}, {{ $statusChart['done'] }}],
                backgroundColor: ['#94a3b8', '#f59e0b', '#10b981'],
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
@endpush
