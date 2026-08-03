@extends('layouts.app')
@section('title', 'Stock History')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Audit Trail Stok</h4>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="product_id" class="form-select">
                <option value="">Semua Produk</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="type" class="form-select">
                <option value="">Semua Tipe</option>
                <option value="purchase" {{ request('type') === 'purchase' ? 'selected' : '' }}>Pembelian</option>
                <option value="sale" {{ request('type') === 'sale' ? 'selected' : '' }}>Penjualan</option>
                <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                <option value="opname" {{ request('type') === 'opname' ? 'selected' : '' }}>Stock Opname</option>
            </select>
        </div>
        <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr>
                    <th>Waktu</th><th>Produk</th><th>Tipe</th><th class="text-end">Sebelum</th>
                    <th class="text-end">Perubahan</th><th class="text-end">Sesudah</th><th>Referensi</th><th>Catatan</th><th>User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($histories as $h)
                <tr>
                    <td>{{ $h->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $h->product->name ?? '-' }}</td>
                    <td>
                        @switch($h->type)
                            @case('purchase')<span class="badge bg-success">Pembelian</span>@break
                            @case('sale')<span class="badge bg-primary">Penjualan</span>@break
                            @case('service')<span class="badge bg-info">Service</span>@break
                            @case('adjustment')<span class="badge bg-warning text-dark">Adjust</span>@break
                            @case('opname')<span class="badge bg-secondary">Opname</span>@break
                            @default<span class="badge bg-light text-dark">{{ $h->type }}</span>
                        @endswitch
                    </td>
                    <td class="text-end">{{ $h->previous_stock }}</td>
                    <td class="text-end">
                        @if($h->quantity_change > 0)
                            <span class="text-success">+{{ $h->quantity_change }}</span>
                        @else
                            <span class="text-danger">{{ $h->quantity_change }}</span>
                        @endif
                    </td>
                    <td class="text-end"><strong>{{ $h->new_stock }}</strong></td>
                    <td>{{ $h->reference_type ? class_basename($h->reference_type) . ' #' . $h->reference_id : '-' }}</td>
                    <td>{{ Str::limit($h->reason, 40) }}</td>
                    <td>{{ $h->user->name ?? 'system' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-3 text-muted">Belum ada audit trail.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $histories->links() }}</div>
</div></div>
@endsection
