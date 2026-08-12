@extends('layouts.app')
@section('title', 'Detail Retur Penjualan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Retur Penjualan</h4>
    <div>
        <a href="{{ route('sell-returns.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi Retur</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="160" class="text-muted">No. Retur</td>
                        <td><strong>{{ $sellReturn->return_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal</td>
                        <td>{{ $sellReturn->return_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pelanggan</td>
                        <td>{{ $sellReturn->customer?->name ?? '-' }}</td>
                    </tr>
                    @if($sellReturn->sale)
                    <tr>
                        <td class="text-muted">Penjualan</td>
                        <td>{{ $sellReturn->sale->sales_no }}</td>
                    </tr>
                    @endif
                    @if($sellReturn->invoice)
                    <tr>
                        <td class="text-muted">Invoice</td>
                        <td>{{ $sellReturn->invoice->invoice_number }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>{!! $sellReturn->status_badge !!}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dibuat Oleh</td>
                        <td>{{ $sellReturn->creator?->name ?? '-' }}</td>
                    </tr>
                    @if($sellReturn->reason)
                    <tr>
                        <td class="text-muted">Alasan</td>
                        <td>{{ $sellReturn->reason }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <small class="text-muted">Total Refund</small>
                <h3 class="mb-0">@money($sellReturn->refund_amount)</h3>
                <small class="text-muted">{{ $sellReturn->items->sum('quantity') }} item</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Item yang Diretur</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sellReturn->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($item->product)
                            <a href="{{ route('products.show', $item->product) }}" class="text-decoration-none">
                                {{ $item->product->name }}
                            </a>
                        @else
                            -
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
                    <td colspan="4" class="text-end"><strong>Total Refund</strong></td>
                    <td class="text-end"><strong>@money($sellReturn->refund_amount)</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
