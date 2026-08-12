@extends('layouts.app')

@section('title', 'Detail Pelanggan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-user me-2"></i>{{ $customer->name }}</h4>
    <div>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center">
                <i class="fas fa-car fa-2x text-primary mb-2"></i>
                <h5 class="mb-0">{{ $stats['vehicle_count'] }}</h5>
                <small class="text-muted">Kendaraan</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center">
                <i class="fas fa-tools fa-2x text-success mb-2"></i>
                <h5 class="mb-0">{{ $stats['total_services'] }}</h5>
                <small class="text-muted">Total Servis</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice fa-2x text-info mb-2"></i>
                <h5 class="mb-0">{{ $customer->invoices()->count() }}</h5>
                <small class="text-muted">Invoice</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-2x text-warning mb-2"></i>
                <h5 class="mb-0">@money($stats['total_spent'] ?? 0)</h5>
                <small class="text-muted">Total Pembayaran</small>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button">
            <i class="fas fa-info-circle me-1"></i>Overview
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#vehicles" type="button">
            <i class="fas fa-car me-1"></i>Kendaraan
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#services" type="button">
            <i class="fas fa-tools me-1"></i>Riwayat Servis
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#invoices" type="button">
            <i class="fas fa-file-invoice me-1"></i>Invoice
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#loyalty" type="button">
            <i class="fas fa-star me-1"></i>Loyalty
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#warranty" type="button">
            <i class="fas fa-shield-alt me-1"></i>Garansi
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#documents" type="button">
            <i class="fas fa-paperclip me-1"></i>Dokumen
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="overview">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Informasi Pelanggan</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th class="w-25">Nama</th><td>{{ $customer->name }}</td></tr>
                            <tr><th>Email</th><td>{{ $customer->email ?? '-' }}</td></tr>
                            <tr><th>Telepon</th><td>{{ $customer->phone ?? '-' }}</td></tr>
                            <tr><th>Kota</th><td>{{ $customer->city ?? '-' }}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th class="w-25">Alamat</th><td>{{ $customer->address ?? '-' }}</td></tr>
                            <tr><th>Kode Pos</th><td>{{ $customer->postal_code ?? '-' }}</td></tr>
                            <tr><th>Catatan</th><td>{{ $customer->notes ?? '-' }}</td></tr>
                            <tr><th>Terdaftar</th><td>{{ $customer->created_at->format('d M Y H:i') }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="vehicles">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No. Plat</th>
                                <th>Tipe</th>
                                <th>Merek</th>
                                <th>Tahun</th>
                                <th>KM</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehicles as $vehicle)
                            <tr>
                                <td><strong>{{ $vehicle->number_plate }}</strong></td>
                                <td>{{ $vehicle->vehicleType->name ?? '-' }}</td>
                                <td>{{ $vehicle->vehicleBrand->name ?? '-' }}</td>
                                <td>{{ $vehicle->year ?? '-' }}</td>
                                <td>{{ $vehicle->odometer ? number_format($vehicle->odometer) . ' km' : '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">Belum ada kendaraan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="services">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Kendaraan</th>
                                <th>Kategori</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $service)
                            <tr>
                                <td>{{ $service->id }}</td>
                                <td>{{ $service->created_at?->format('d M Y') }}</td>
                                <td>{{ $service->vehicle?->number_plate ?? '-' }}</td>
                                <td>{{ $service->repairCategory?->repair_category_name ?? '-' }}</td>
                                <td>
                                    @php
                                        $svcLabels = [0 => 'Pending', 1 => 'In Progress', 2 => 'Done'];
                                        $svcColors = [0 => 'secondary', 1 => 'warning', 2 => 'success'];
                                    @endphp
                                    <span class="badge bg-{{ $svcColors[$service->done_status] ?? 'secondary' }}">
                                        {{ $svcLabels[$service->done_status] ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">Belum ada riwayat servis.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="invoices">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#Invoice</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                                <td>{{ $invoice->created_at?->format('d M Y') }}</td>
                                <td>@money($invoice->grand_total ?? 0)</td>
                                <td>
                                    <span class="badge bg-{{ ($invoice->status ?? '') === 'paid' ? 'success' : 'warning' }}">
                                        {{ $invoice->status ?? '-' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Belum ada invoice.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="loyalty">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Poin Loyalty</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                                <h5 class="mb-0">{{ number_format($customer->loyalty_points ?? 0) }}</h5>
                                <small class="text-muted">Total Poin</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-crown fa-2x text-info mb-2"></i>
                                <h5 class="mb-0">{{ $customer->membership_tier ?? 'Standard' }}</h5>
                                <small class="text-muted">Membership Tier</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                <h5 class="mb-0">{{ $loyaltyTransactions->count() }}</h5>
                                <small class="text-muted">Total Transaksi</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Riwayat Transaksi Poin</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Poin</th>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loyaltyTransactions as $trans)
                            <tr>
                                <td>{{ $trans->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    @php
                                        $typeColors = ['earn' => 'success', 'redeem' => 'danger', 'adjust' => 'warning', 'expire' => 'secondary'];
                                    @endphp
                                    <span class="badge bg-{{ $typeColors[$trans->type] ?? 'secondary' }}">
                                        {{ $trans->type }}
                                    </span>
                                </td>
                                <td><strong>{{ $trans->points > 0 ? '+' . $trans->points : $trans->points }}</strong></td>
                                <td>{{ $trans->description ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">Belum ada transaksi loyalty.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="warranty">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Tanggal Klaim</th>
                                <th>Keluhan</th>
                                <th>Status</th>
                                <th>Resolusi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($warrantyClaims as $claim)
                            <tr>
                                <td>{{ $claim->id }}</td>
                                <td>{{ $claim->claim_date?->format('d M Y') }}</td>
                                <td>{{ $claim->complaint ?? '-' }}</td>
                                <td>
                                    @php
                                        $wStatusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'resolved' => 'info'];
                                    @endphp
                                    <span class="badge bg-{{ $wStatusColors[$claim->status] ?? 'secondary' }}">
                                        {{ $claim->status ?? '-' }}
                                    </span>
                                </td>
                                <td>{{ $claim->resolution ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Belum ada klaim garansi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="documents">
        @include('partials.media-attachments', ['attachable' => $customer, 'attachableType' => 'customer'])
    </div>
</div>
@endsection
