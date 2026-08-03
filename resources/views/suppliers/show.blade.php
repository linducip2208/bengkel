@extends('layouts.app')
@section('title', 'Detail Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Supplier</h4>
    <div>
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi Supplier</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Nama</td>
                        <td><strong>{{ $supplier->name }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kontak Person</td>
                        <td>{{ $supplier->contact_person ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Email</td>
                        <td>{{ $supplier->email ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Telepon</td>
                        <td>{{ $supplier->phone ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">NPWP</td>
                        <td>{{ $supplier->tax_id ?: '-' }}</td>
                    </tr>
                    @if($supplier->address)
                    <tr>
                        <td class="text-muted">Alamat</td>
                        <td>{{ $supplier->address }}</td>
                    </tr>
                    @endif
                    @if($supplier->notes)
                    <tr>
                        <td class="text-muted">Catatan</td>
                        <td>{{ $supplier->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Produk ({{ $supplier->products->count() }})</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Produk</th>
                            <th class="text-center">Stok</th>
                            <th class="text-end">Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplier->products as $product)
                        <tr>
                            <td><small class="text-muted">{{ $product->code }}</small></td>
                            <td>
                                <a href="{{ route('products.show', $product) }}" class="text-decoration-none">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td class="text-center">{{ $product->current_stock }}</td>
                            <td class="text-end">@money($product->price)</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Belum ada produk dari supplier ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h6 class="card-title mb-0">Riwayat Pembelian</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. PO</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Item</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $purchase)
                <tr>
                    <td>
                        <a href="{{ route('purchases.show', $purchase) }}" class="text-decoration-none">
                            {{ $purchase->purchase_no }}
                        </a>
                    </td>
                    <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                    <td>{!! $purchase->status_badge !!}</td>
                    <td class="text-end">@money($purchase->total_amount)</td>
                    <td class="text-center">{{ $purchase->items_count }}</td>
                    <td class="text-center">
                        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat pembelian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($purchases->hasPages())
    <div class="card-footer">
        {{ $purchases->links() }}
    </div>
    @endif
</div>
@endsection
