@extends('layouts.app')
@section('title', 'Detail Purchase Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Purchase Order</h4>
    <div>
        @if($purchase->status === 'ordered')
            <form action="{{ route('purchases.mark-received', $purchase) }}" method="POST" class="d-inline" onsubmit="return confirm('Konfirmasi penerimaan barang? Stok akan otomatis bertambah.')">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle"></i> Tandai Diterima
                </button>
            </form>
        @endif
        @if($purchase->status === 'draft')
            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endif
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">
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
                        <td><strong>{{ $purchase->purchase_no }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal</td>
                        <td>{{ $purchase->purchase_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>{!! $purchase->status_badge !!}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat Oleh</td>
                        <td>{{ $purchase->creator?->name ?? '-' }}</td>
                    </tr>
                    @if($purchase->notes)
                    <tr>
                        <td class="text-muted">Catatan</td>
                        <td>{{ $purchase->notes }}</td>
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
                <h3 class="mb-0">@money($purchase->total_amount)</h3>
                <small class="text-muted">{{ $purchase->items->sum('quantity') }} item</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Item Pembelian</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Kode</th>
                    <th>Nama Produk</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><small class="text-muted">{{ $item->product?->code ?? '-' }}</small></td>
                    <td>
                        @if($item->product)
                            <a href="{{ route('products.show', $item->product) }}" class="text-decoration-none">
                                {{ $item->product->name }}
                            </a>
                        @else
                            <span class="text-muted">Produk dihapus</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">@money($item->unit_price)</td>
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
                    <td colspan="5" class="text-end"><strong>Grand Total</strong></td>
                    <td class="text-end"><strong>@money($purchase->total_amount)</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@if($purchase->historyRecords->isNotEmpty())
<div class="card mt-3">
    <div class="card-header">
        <h6 class="card-title mb-0">Riwayat Status</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->historyRecords as $record)
                <tr>
                    <td>{{ $record->changed_at ? $record->changed_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>{!! $purchase->status_badge !!}</td>
                    <td>{{ $record->notes ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
