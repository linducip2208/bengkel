@extends('layouts.app')
@section('title', 'Daftar Pembelian')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Daftar Pembelian</h4>
    <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Buat PO Baru
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('purchases.index') }}" class="row g-2">
            <div class="col-md-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari no. PO..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="ordered" {{ request('status') == 'ordered' ? 'selected' : '' }}>Dipesan</option>
                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Diterima</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
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
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="Dari">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="Sampai">
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
                @forelse($purchases as $purchase)
                <tr>
                    <td>
                        <a href="{{ route('purchases.show', $purchase) }}" class="text-decoration-none">
                            <strong>{{ $purchase->purchase_no }}</strong>
                        </a>
                    </td>
                    <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                    <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                    <td class="text-center">{{ $purchase->items_count }}</td>
                    <td class="text-end">@money($purchase->total_amount)</td>
                    <td>{!! $purchase->status_badge !!}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-outline-info" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($purchase->status === 'draft')
                            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus purchase order ini?')">
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
        <small class="text-muted">{{ $purchases->total() }} PO</small>
        {{ $purchases->withQueryString()->links() }}
    </div>
</div>
@endsection
