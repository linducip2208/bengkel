@extends('layouts.app')
@section('title', 'Detail Klaim Supplier')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-hand-holding-usd me-2"></i>Detail Klaim {{ $supplierClaim->claim_number }}</h4>
    <a href="{{ route('supplier-claims.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Informasi Klaim</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:150px;">No. Klaim</td><td><code>{{ $supplierClaim->claim_number }}</code></td></tr>
                    <tr><td>Supplier</td><td>{{ $supplierClaim->supplier?->name ?? '-' }}</td></tr>
                    <tr><td>Produk</td><td>{{ $supplierClaim->product?->name ?? '-' }}</td></tr>
                    <tr><td>Klaim Garansi</td><td>@if($supplierClaim->warranty_claim_id) #{{ $supplierClaim->warranty_claim_id }} @else — @endif</td></tr>
                    <tr><td>Status</td><td>
                        @php
                            $badges = ['pending'=>'secondary','submitted'=>'info','approved'=>'success','rejected'=>'danger','paid'=>'primary'];
                        @endphp
                        <span class="badge bg-{{ $badges[$supplierClaim->status] ?? 'secondary' }}">{{ ucfirst($supplierClaim->status) }}</span>
                    </td></tr>
                </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Nilai</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:150px;">Kuantitas</td><td>{{ number_format((float) $supplierClaim->quantity, 0, ',', '.') }}</td></tr>
                    <tr><td>Nilai Klaim</td><td>@money($supplierClaim->claim_amount)</td></tr>
                    <tr><td>Dibuat</td><td>{{ $supplierClaim->created_at?->format('d M Y H:i') }}</td></tr>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Catatan</strong></div>
            <div class="card-body"><p class="mb-0">{{ $supplierClaim->notes ?? '-' }}</p></div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Aksi</strong></div>
            <div class="card-body">
                @if($supplierClaim->status === 'pending' || $supplierClaim->status === 'submitted')
                <form action="{{ route('supplier-claims.approve', $supplierClaim) }}" method="POST" class="d-inline me-1">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Setujui</button>
                </form>
                <form action="{{ route('supplier-claims.reject', $supplierClaim) }}" method="POST" class="d-inline me-1">
                    @csrf
                    <div class="d-inline-flex align-items-center gap-1">
                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Alasan tolak" style="width:180px;">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times me-1"></i>Tolak</button>
                    </div>
                </form>
                @endif
                @if($supplierClaim->status === 'approved')
                <form action="{{ route('supplier-claims.mark-paid', $supplierClaim) }}" method="POST" class="d-inline me-1">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-money-bill-wave me-1"></i>Tandai Dibayar</button>
                </form>
                @endif
                <form action="{{ route('supplier-claims.destroy', $supplierClaim) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus klaim ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
