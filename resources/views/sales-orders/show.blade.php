@extends('layouts.app')
@section('title', 'Detail Sales Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Sales Order</h4>
    <div>
        @if($salesOrder->status === 'draft' || $salesOrder->status === 'sent')
            <form action="{{ route('sales-orders.approve', $salesOrder) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle"></i> Setujui
                </button>
            </form>
            <form action="{{ route('sales-orders.reject', $salesOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('Tolak sales order ini?')">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-x-circle"></i> Tolak
                </button>
            </form>
        @endif
        @if(in_array($salesOrder->status, ['draft', 'sent', 'approved']))
            <form action="{{ route('sales-orders.convert', $salesOrder) }}" method="POST" class="d-inline" onsubmit="return confirm('Konversi sales order menjadi invoice?')">
                @csrf
                <button type="submit" class="btn btn-info btn-sm">
                    <i class="bi bi-file-earmark-arrow-up"></i> Jadikan Invoice
                </button>
            </form>
        @endif
        @if($salesOrder->status === 'draft')
            <a href="{{ route('sales-orders.edit', $salesOrder) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endif
        <a href="{{ route('sales-orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi Sales Order</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="150" class="text-muted">No. SO</td>
                        <td><strong>{{ $salesOrder->order_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Customer</td>
                        <td>{{ $salesOrder->customer?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kendaraan</td>
                        <td>{{ $salesOrder->vehicle?->number_plate ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cabang</td>
                        <td>{{ $salesOrder->branch?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal</td>
                        <td>{{ $salesOrder->order_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>{!! $salesOrder->status_badge !!}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat Oleh</td>
                        <td>{{ $salesOrder->creator?->name ?? '-' }}</td>
                    </tr>
                    @if($salesOrder->notes)
                    <tr>
                        <td class="text-muted">Catatan</td>
                        <td>{{ $salesOrder->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center mb-3">
            <div class="card-body">
                <small class="text-muted">Grand Total</small>
                <h3 class="mb-0">@money($salesOrder->grand_total)</h3>
                <small class="text-muted">{{ $salesOrder->items->sum('quantity') }} item</small>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Subtotal</td>
                        <td class="text-end">@money($salesOrder->subtotal)</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Diskon</td>
                        <td class="text-end">- @money($salesOrder->discount)</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pajak</td>
                        <td class="text-end">@money($salesOrder->tax_amount)</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Item Sales Order</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Produk / Deskripsi</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Diskon</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesOrder->items as $index => $item)
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
                    <td class="text-end">@money($item->discount)</td>
                    <td class="text-end">@money($item->total_price)</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">Tidak ada item.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="5" class="text-end"><strong>Subtotal</strong></td>
                    <td class="text-end"><strong>@money($salesOrder->subtotal)</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
