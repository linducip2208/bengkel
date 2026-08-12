@extends('layouts.app')
@section('title', 'Purchase Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Purchase Orders</h4>
    <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Buat Purchase Order
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase-orders.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari no. PO / supplier..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Terkirim</option>
                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
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
                    <th>No. PO</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th class="text-center">Item</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrders as $purchaseOrder)
                <tr>
                    <td>
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="text-decoration-none">
                            <strong>{{ $purchaseOrder->po_number }}</strong>
                        </a>
                    </td>
                    <td>{{ $purchaseOrder->order_date->format('d/m/Y') }}</td>
                    <td>{{ $purchaseOrder->supplier?->name ?? '-' }}</td>
                    <td class="text-center">{{ $purchaseOrder->items_count }}</td>
                    <td class="text-end">@money($purchaseOrder->grand_total)</td>
                    <td>{!! $purchaseOrder->status_badge !!}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-outline-info" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($purchaseOrder->status === 'draft')
                            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('purchase-orders.destroy', $purchaseOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus purchase order ini?')">
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
                    <td colspan="7" class="text-center text-muted py-4">Tidak ada purchase order ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $purchaseOrders->total() }} purchase order</small>
        {{ $purchaseOrders->withQueryString()->links() }}
    </div>
</div>
@endsection
