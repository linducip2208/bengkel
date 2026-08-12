@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Sales Orders</h4>
    <a href="{{ route('sales-orders.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Buat Sales Order
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('sales-orders.index') }}" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari no. SO / pelanggan..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Terkirim</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Menjadi Invoice</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. SO</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th class="text-center">Item</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesOrders as $salesOrder)
                <tr>
                    <td>
                        <a href="{{ route('sales-orders.show', $salesOrder) }}" class="text-decoration-none">
                            <strong>{{ $salesOrder->order_number }}</strong>
                        </a>
                    </td>
                    <td>{{ $salesOrder->order_date->format('d/m/Y') }}</td>
                    <td>{{ $salesOrder->customer?->name ?? '-' }}</td>
                    <td class="text-center">{{ $salesOrder->items_count }}</td>
                    <td class="text-end">@money($salesOrder->grand_total)</td>
                    <td>{!! $salesOrder->status_badge !!}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('sales-orders.show', $salesOrder) }}" class="btn btn-outline-info" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($salesOrder->status === 'draft')
                            <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('sales-orders.destroy', $salesOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus sales order ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Tidak ada sales order ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $salesOrders->total() }} sales order</small>
        {{ $salesOrders->withQueryString()->links() }}
    </div>
</div>
@endsection
