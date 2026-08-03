@extends('layouts.app')
@section('title', 'Customer Lifetime Value')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-crown me-2 text-warning"></i>Customer Lifetime Value</h4>
</div>
<div class="row mb-3">
    @php $top = $topCustomers->first(); @endphp
    @if($top)
    <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center"><h5>{{ $top->name }}</h5><small class="text-warning">Top Customer</small></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h5>@money($top->lifetime_value)</h5><small>Lifetime Value</small></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h5>{{ $top->services_count }}x</h5><small>Total Kunjungan</small></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><h5>@money($top->avg_per_visit)</h5><small>Avg per Visit</small></div></div></div>
    @endif
</div>
<div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>#</th><th>Customer</th><th class="text-center">Kunjungan</th><th class="text-end">Lifetime Value</th><th class="text-end">Avg/Visit</th><th>Kunjungan Terakhir</th></tr></thead>
    <tbody>
        @forelse($topCustomers as $c)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td><strong>{{ $c->name }}</strong><br><small class="text-muted">{{ $c->phone }}</small></td>
            <td class="text-center">{{ $c->services_count }}</td>
            <td class="text-end fw-bold text-success">@money($c->lifetime_value)</td>
            <td class="text-end">@money($c->avg_per_visit)</td>
            <td>{{ $c->last_service?->format('d M Y') ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada data customer.</td></tr>
        @endforelse
    </tbody>
</table>
</div></div>
@endsection
