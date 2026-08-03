@extends('layouts.app')
@section('title', 'Daftar Invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Invoice</h4>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Invoice</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="half_paid" {{ request('status') === 'half_paid' ? 'selected' : '' }}>Dibayar Sebagian</option>
                    <option value="full_paid" {{ request('status') === 'full_paid' ? 'selected' : '' }}>Lunas</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="invoice_type" class="form-select">
                    <option value="">Semua Tipe</option>
                    <option value="service" {{ request('invoice_type') === 'service' ? 'selected' : '' }}>Service</option>
                    <option value="sales" {{ request('invoice_type') === 'sales' ? 'selected' : '' }}>Sales</option>
                    <option value="sales_part" {{ request('invoice_type') === 'sales_part' ? 'selected' : '' }}>Sales Part</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Dari">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="Sampai">
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari pelanggan/invoice...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>No. Invoice</th>
                <th>Pelanggan</th>
                <th>Tipe</th>
                <th>Tanggal</th>
                <th>Total</th>
                <th>Dibayar</th>
                <th>Sisa</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                @php
                    $totalPaid = $invoice->paymentRecords->sum('amount');
                    $remaining = max($invoice->grand_total - $totalPaid, 0);
                @endphp
                <tr>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ $invoice->customer->name ?? '-' }}</td>
                    <td><span class="badge bg-secondary">{{ $invoice->invoice_type }}</span></td>
                    <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    <td>@money($invoice->grand_total)</td>
                    <td>@money($totalPaid)</td>
                    <td>@money($remaining)</td>
                    <td>
                        @if ($invoice->status === 'full_paid')
                            <span class="badge bg-success">Lunas</span>
                        @elseif ($invoice->status === 'half_paid')
                            <span class="badge bg-warning text-dark">Sebagian</span>
                        @else
                            <span class="badge bg-danger">Belum</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-sm btn-info" title="Lihat"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-secondary" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a>
                            @if ($remaining > 0)
                                <a href="{{ route('payments.create', $invoice) }}" class="btn btn-sm btn-success" title="Bayar"><i class="bi bi-cash-coin"></i></a>
                            @endif
                            <a href="{{ route('invoices.sendWA', $invoice) }}" class="btn btn-sm btn-success" title="Kirim WA" target="_blank"><i class="bi bi-whatsapp"></i></a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada invoice.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $invoices->links() }}
@endsection
