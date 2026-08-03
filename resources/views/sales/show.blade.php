@extends('layouts.app')
@section('title', 'Detail Penjualan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Detail Penjualan</h4>
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
                <table class="table table-borderless">
                    <tr><td width="150"><strong>Pelanggan</strong></td><td>{{ $sale->customer->name ?? '-' }}</td></tr>
                    <tr><td><strong>Kendaraan</strong></td><td>{{ $sale->vehicle?->vehicleBrand?->name }} {{ $sale->vehicle?->model_name }} ({{ $sale->vehicle?->license_plate }})</td></tr>
                    <tr><td><strong>Tahun</strong></td><td>{{ $sale->vehicle?->year }}</td></tr>
                    <tr><td><strong>Harga Jual</strong></td><td><strong>@money($sale->price)</strong></td></tr>
                    <tr><td><strong>Uang Muka</strong></td><td>@money($sale->down_payment)</td></tr>
                    <tr><td><strong>Sisa Pembayaran</strong></td><td><strong class="text-danger">@money(max($sale->price - $sale->down_payment, 0))</strong></td></tr>
                </table>
                @if ($sale->description)
                    <hr>
                    <small class="text-muted">Deskripsi:</small>
                    <p>{{ $sale->description }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><strong>Invoice Terkait</strong></div>
            <div class="card-body p-0">
                @if ($sale->invoices->count() > 0)
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
                @else
                    <div class="p-3 text-muted text-center">Belum ada invoice.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
