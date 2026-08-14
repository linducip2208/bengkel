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
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h4>
    <a href="{{ route('dashboard.config') }}" class="btn btn-outline-secondary btn-sm" title="Atur widget dashboard">
        <i class="fas fa-cog me-1"></i>Atur Widget
    </a>
</div>

{{-- Low Stock Alert — sekali per session --}}
@if(in_array('low_stock', $enabledWidgets) && $lowStockAlert)
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
@if(in_array('role_widgets', $enabledWidgets) && !empty($roleWidgets))
<div class="row mb-3">
    @isset($roleWidgets['revenue'])
    <div class="col-md-3 mb-2">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Pemasukan Bulan Ini</small>
                <h5 class="mb-0 text-success">@money($roleWidgets['revenue'])</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-danger border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Pengeluaran Bulan Ini</small>
                <h5 class="mb-0 text-danger">@money($roleWidgets['expense'])</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-primary border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Profit Bulan Ini</small>
                <h5 class="mb-0 text-primary">@money($roleWidgets['profit'])</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-info border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Total Invoice</small>
                <h5 class="mb-0 text-info">{{ $roleWidgets['total_invoices'] }} <small class="text-muted">/ {{ $roleWidgets['unpaid_invoices'] }} unpaid</small></h5>
            </div>
        </div>
    </div>
    @endisset

    @isset($roleWidgets['services_today'])
    <div class="col-md-3 mb-2">
        <div class="card border-start border-info border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Service Hari Ini</small>
                <h5 class="mb-0 text-info">{{ $roleWidgets['services_today'] }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-warning border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Service Pending</small>
                <h5 class="mb-0 text-warning">{{ $roleWidgets['services_pending'] }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Selesai Hari Ini</small>
                <h5 class="mb-0 text-success">{{ $roleWidgets['services_completed'] }}</h5>
            </div>
        </div>
    </div>
    @endisset

    @isset($roleWidgets['my_pending'])
    <div class="col-md-3 mb-2">
        <div class="card border-start border-warning border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Task Pending Saya</small>
                <h5 class="mb-0 text-warning">{{ $roleWidgets['my_pending'] }} service</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Selesai Hari Ini (Saya)</small>
                <h5 class="mb-0 text-success">{{ $roleWidgets['my_completed_today'] }} service</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-primary border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Komisi Belum Dibayar</small>
                <h5 class="mb-0 text-primary">@money($roleWidgets['my_commission'])</h5>
            </div>
        </div>
    </div>
    @endisset

    @isset($roleWidgets['pos_today'])
    <div class="col-md-3 mb-2">
        <div class="card border-start border-info border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Transaksi POS Hari Ini</small>
                <h5 class="mb-0 text-info">{{ $roleWidgets['pos_today'] }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-success border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Revenue POS Hari Ini</small>
                <h5 class="mb-0 text-success">@money($roleWidgets['pos_revenue_today'])</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="card border-start border-primary border-4 h-100">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Saldo Kas Saat Ini</small>
                <h5 class="mb-0 text-primary">@money($roleWidgets['pos_balance'])</h5>
            </div>
        </div>
    </div>
    @endisset
</div>
@endif

{{-- Stats Cards — clickable drill-down --}}
@if(in_array('stat_cards', $enabledWidgets))
<div class="row mb-4">
    <div class="col-md-2 mb-3">
        <a href="{{ route('services.index', ['status' => 'active']) }}" class="text-decoration-none">
        <div class="card border-primary h-100" style="transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div class="card-body text-center">
                <h5 class="card-title text-primary">{{ $stats['open_services'] }}</h5>
                <p class="card-text small">Active Services</p>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-2 mb-3">
        <a href="{{ route('services.index', ['status' => '12']) }}" class="text-decoration-none">
        <div class="card border-success h-100" style="transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div class="card-body text-center">
                <h5 class="card-title text-success">{{ $stats['completed_today'] }}</h5>
                <p class="card-text small">Completed Today</p>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-2 mb-3">
        <a href="{{ route('reports.financial') }}" class="text-decoration-none">
        <div class="card border-info h-100" style="transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div class="card-body text-center">
                <h5 class="card-title text-info">@money($stats['revenue_today'])</h5>
                <p class="card-text small">Today's Revenue</p>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-2 mb-3">
        <a href="{{ route('reports.financial') }}" class="text-decoration-none">
        <div class="card border-secondary h-100" style="transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div class="card-body text-center">
                <h5 class="card-title">@money($stats['revenue_this_month'])</h5>
                <p class="card-text small">Monthly Revenue</p>
            </div>
        </div>
        </a>
    </div>
    <div class="col-md-2 mb-3">
        <a href="{{ route('invoices.index', ['status' => 'unpaid']) }}" class="text-decoration-none">
        <div class="card border-warning h-100" style="transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div class="card-body text-center">
                <h5 class="card-title text-warning">{{ $stats['outstanding_invoices'] }}</h5>
                <p class="card-text small">Outstanding Invoices</p>
            </div>
        </div>
    </div>
    <div class="col-md-2 mb-3">
        <a href="{{ route('reports.stock') }}" class="text-decoration-none">
        <div class="card border-danger h-100" style="transition:transform 0.15s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div class="card-body text-center">
                <h5 class="card-title text-danger">{{ $stats['low_stock_count'] }}</h5>
                <p class="card-text small">Low Stock Items</p>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Auto PO Suggestion: Low Stock Reorder Table --}}
@if(in_array('low_stock', $enabledWidgets) && $lowStockAlert && !empty($lowStockReorder))
<div class="card mb-4 border-danger">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-boxes me-2"></i>Rekomendasi Reorder Produk (Auto PO)</h6>
        <span class="badge bg-light text-danger">{{ count($lowStockReorder) }} item</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produk</th>
                        <th class="text-center">Stok</th>
                        <th class="text-center">Min</th>
                        <th class="text-center">Saran Reorder</th>
                        <th class="text-end">Harga Beli Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockReorder as $item)
                    <tr>
                        <td>
                            <small>{{ $item['sku'] }}</small><br>
                            <strong>{{ $item['product_name'] }}</strong>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $item['current_stock'] <= 0 ? 'danger' : 'warning' }}">{{ $item['current_stock'] }}</span>
                        </td>
                        <td class="text-center">{{ $item['minimum_stock'] }}</td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $item['suggested_reorder'] }}</span>
                        </td>
                        <td class="text-end">@include('partials.rupiah', ['amount' => $item['last_purchase_price']])</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-light d-flex justify-content-end gap-2">
        <a href="{{ route('reports.stock') }}" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-chart-bar me-1"></i> Laporan Stok
        </a>
        <a href="{{ route('products.reorder') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-cart-plus me-1"></i> Buat PO Otomatis
        </a>
    </div>
</div>
@endif

{{-- Charts Row --}}
@if(in_array('revenue_chart', $enabledWidgets) || in_array('status_chart', $enabledWidgets))
<div class="row mb-4">
    @if(in_array('revenue_chart', $enabledWidgets))
    <div class="col-md-8 mb-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Revenue & Expenses (14 Hari)</h6></div>
            <div class="card-body">
                <canvas id="revenueChart" height="260"></canvas>
            </div>
        </div>
    </div>
    @endif
    @if(in_array('status_chart', $enabledWidgets))
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Status Service Hari Ini</h6></div>
            <div class="card-body">
                <canvas id="statusChart" height="260"></canvas>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- Quick Actions --}}
<div class="row mb-4 no-print">
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
    @if(in_array('recent_services', $enabledWidgets))
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
                            <td>{{ $service->customer?->name ?? '-' }}</td>
                            <td>{{ $service->vehicle?->number_plate ?? '-' }}</td>
                            <td><span class="badge bg-{{ $service->status_color }} bg-opacity-10 text-{{ $service->status_color }} rounded-pill">{{ $service->status_label }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center">No recent services</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    @if(in_array('upcoming_services', $enabledWidgets))
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
<td>{{ $service->customer?->name ?? '-' }}</td>
<td>{{ $service->vehicle?->number_plate ?? '-' }}</td>
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
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueEl = document.getElementById('revenueChart');
    if (revenueEl) {
    const ctx1 = revenueEl.getContext('2d');
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
    }

    // Status Pie Chart
    const statusEl = document.getElementById('statusChart');
    if (statusEl) {
    const ctx2 = statusEl.getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Booked','Checked In','Inspection','Waiting Appr','Approved','In Progress','Waiting Parts','QC','Ready','Invoiced','Paid','Released','Completed'],
            datasets: [{
                data: [
                    {{ $statusChart['booked'] }}, {{ $statusChart['checked_in'] }},
                    {{ $statusChart['inspection'] }}, {{ $statusChart['waiting_approval'] }},
                    {{ $statusChart['approved'] }}, {{ $statusChart['in_progress'] }},
                    {{ $statusChart['waiting_parts'] }}, {{ $statusChart['qc'] }},
                    {{ $statusChart['ready'] }}, {{ $statusChart['invoiced'] }},
                    {{ $statusChart['paid'] }}, {{ $statusChart['released'] }},
                    {{ $statusChart['completed'] }}
                ],
                backgroundColor: ['#94a3b8','#0ea5e9','#6366f1','#f59e0b','#10b981','#3b82f6','#ef4444','#8b5cf6','#14b8a6','#a855f7','#22c55e','#1e293b','#059669'],
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
    }
});
</script>
@endpush
