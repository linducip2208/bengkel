@extends('layouts.app')
@section('title', 'Loyalty Points')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-stars me-2"></i>Loyalty Points & Membership</h4>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card border-warning"><div class="card-body text-center">
        <h3 class="text-warning">{{ number_format($summary['total_points']) }}</h3>
        <small class="text-muted">Total Poin Beredar</small>
    </div></div></div>
    <div class="col-md-9">
        <div class="card"><div class="card-body">
            <h6 class="mb-2"><i class="bi bi-trophy me-1"></i>Top 5 Customer</h6>
            <div class="d-flex gap-2 flex-wrap">
                @foreach($summary['top_customers'] as $i => $c)
                <div class="border rounded p-2 small">
                    <strong>#{{ $i+1 }} {{ $c->name }}</strong>
                    <span class="badge bg-info ms-1">{{ ucfirst($c->membership_tier) }}</span>
                    <div class="text-warning fw-bold">{{ number_format($c->loyalty_points) }} poin</div>
                </div>
                @endforeach
            </div>
        </div></div>
    </div>
</div>

<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari customer..."></div>
        <div class="col-md-3"><select name="tier" class="form-select">
            <option value="">Semua Tier</option>
            <option value="bronze" {{ request('tier') === 'bronze' ? 'selected' : '' }}>Bronze</option>
            <option value="silver" {{ request('tier') === 'silver' ? 'selected' : '' }}>Silver</option>
            <option value="gold" {{ request('tier') === 'gold' ? 'selected' : '' }}>Gold</option>
            <option value="platinum" {{ request('tier') === 'platinum' ? 'selected' : '' }}>Platinum</option>
        </select></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Customer</th><th>HP</th><th>Total Service</th><th>Tier</th><th class="text-end">Poin</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($customers as $c)
            <tr>
                <td><a href="{{ route('loyalty.show', $c) }}">{{ $c->name }}</a></td>
                <td>{{ $c->phone }}</td>
                <td>{{ $c->invoices_count ?? 0 }}</td>
                <td>
                    @php $color = ['bronze'=>'secondary','silver'=>'light text-dark','gold'=>'warning text-dark','platinum'=>'info'][$c->membership_tier] ?? 'secondary'; @endphp
                    <span class="badge bg-{{ $color }}">{{ ucfirst($c->membership_tier) }}</span>
                </td>
                <td class="text-end fw-bold text-warning">{{ number_format($c->loyalty_points) }}</td>
                <td><a href="{{ route('loyalty.show', $c) }}" class="btn btn-sm btn-info" title="Detail"><i class="bi bi-eye"></i></a></td>
            </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-3">Belum ada customer.</td></tr>@endforelse
        </tbody>
    </table>
    </div>
    {{ $customers->links() }}
</div></div>
@endsection
