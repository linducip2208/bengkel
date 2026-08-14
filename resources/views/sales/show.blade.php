@extends('layouts.app')
@section('title', 'Detail Penjualan Sparepart')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Detail Penjualan Sparepart</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('sales.edit', $sale) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header"><strong>Informasi Penjualan</strong></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">Status</small>
                        <div>
                            @if ($sale->status === 'completed')
                                <span class="badge bg-success">Selesai</span>
                            @elseif ($sale->status === 'cancelled')
                                <span class="badge bg-danger">Batal</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted">Tanggal</small>
                        <div>{{ $sale->sale_date->format('d M Y') }}</div>
                    </div>
                </div>
                <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <tr><td width="150"><strong>No. Penjualan</strong></td><td>{{ $sale->sales_no }}</td></tr>
                    <tr><td><strong>Pelanggan</strong></td><td>{{ $sale->customer->name ?? 'Walk-in' }}</td></tr>
                    <tr><td><strong>Total</strong></td><td><strong>@money($sale->grand_total)</strong></td></tr>
                </table>
                </div>
                @if ($sale->notes)
                    <hr>
                    <small class="text-muted">Catatan:</small>
                    <p>{{ $sale->notes }}</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Item Sparepart</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sale->items as $item)
                            <tr>
                                <td>{{ $item->product?->name ?? '—' }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">@money($item->unit_price)</td>
                                <td class="text-end">@money($item->total_price)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada item.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Grand Total</th>
                            <th class="text-end">@money($sale->grand_total)</th>
                        </tr>
                    </tfoot>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Invoice Terkait</strong></div>
            <div class="card-body p-0">
                @if ($sale->invoices->count() > 0)
                    <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>No. Invoice</th><th>Status</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($sale->invoices as $inv)
                                <tr>
                                    <td><a href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
                                    <td>
                                        @if ($inv->status === 'full_paid') <span class="badge bg-success">Lunas</span>
                                        @elseif ($inv->status === 'half_paid') <span class="badge bg-warning">Sebagian</span>
                                        @else <span class="badge bg-danger">Belum</span> @endif
                                    </td>
                                    <td class="text-end">@money($inv->grand_total)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @else
                    <div class="p-3 text-muted text-center">Belum ada invoice.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
