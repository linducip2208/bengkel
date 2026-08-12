@extends('layouts.app')
@section('title', 'Detail Purchase Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Purchase Order</h4>
    <div>
        @if(in_array($purchaseOrder->status, ['draft', 'sent']))
            <form action="{{ route('purchase-orders.mark-received', $purchaseOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('Konfirmasi penerimaan barang? Purchase order akan diubah menjadi purchase & stok bertambah.')">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle"></i> Tandai Diterima
                </button>
            </form>
        @endif
        @if($purchaseOrder->status === 'draft')
            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endif
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi Purchase Order</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="150" class="text-muted">No. PO</td>
                        <td><strong>{{ $purchaseOrder->po_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td>{{ $purchaseOrder->supplier?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cabang</td>
                        <td>{{ $purchaseOrder->branch?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal</td>
                        <td>{{ $purchaseOrder->order_date->format('d F Y') }}</td>
                    </tr>
                    @if($purchaseOrder->expected_date)
                    <tr>
                        <td class="text-muted">Estimasi Tiba</td>
                        <td>{{ $purchaseOrder->expected_date->format('d F Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>{!! $purchaseOrder->status_badge !!}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat Oleh</td>
                        <td>{{ $purchaseOrder->creator?->name ?? '-' }}</td>
                    </tr>
                    @if($purchaseOrder->notes)
                    <tr>
                        <td class="text-muted">Catatan</td>
                        <td>{{ $purchaseOrder->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Total Pembelian</small>
                <h3 class="mb-0">@money($purchaseOrder->grand_total)</h3>
                <small class="text-muted">{{ $purchaseOrder->items->sum('quantity') }} item</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Item Purchase Order</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Produk / Deskripsi</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseOrder->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($item->product)
                            <a href="{{ route('products.show', $item->product) }}" class="text-decoration-none">
                                {{ $item->product->name }}
                            </a>
                        @endif
                        @if($item->description && (!$item->product || $item->description !== $item->product->name))
                            <div class="text-muted small">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">@money($item->unit_price)</td>
                    <td class="text-end">@money($item->total_price)</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">Tidak ada item.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                    <td class="text-end"><strong>@money($purchaseOrder->subtotal)</strong></td>
                </tr>
                @if($purchaseOrder->tax_amount > 0)
                <tr>
                    <td colspan="4" class="text-end">Pajak</td>
                    <td class="text-end">@money($purchaseOrder->tax_amount)</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                    <td class="text-end"><strong>@money($purchaseOrder->grand_total)</strong></td>
                </tr>
                @endif
            </tfoot>
        </table>
    </div>
</div>
@endsection
