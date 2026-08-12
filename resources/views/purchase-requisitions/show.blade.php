@extends('layouts.app')
@section('title', 'Detail Permintaan Pembelian')

@php
    $canApprove = auth()->user()->hasAnyRole(['super_admin', 'admin', 'manager']);
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Permintaan Pembelian</h4>
    <div>
        @if($purchaseRequisition->status === 'draft')
            <form action="{{ route('purchase-requisitions.submit', $purchaseRequisition) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-send"></i> Ajukan
                </button>
            </form>
        @endif

        @if($purchaseRequisition->status === 'submitted' && $canApprove)
            <form action="{{ route('purchase-requisitions.approve', $purchaseRequisition) }}" method="POST" class="d-inline" onsubmit="return confirm('Setujui permintaan ini?')">
                @csrf
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check-circle"></i> Setujui
                </button>
            </form>
            <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle"></i> Tolak
            </button>
        @endif

        @if($purchaseRequisition->status === 'approved' && $canApprove)
            <form action="{{ route('purchase-requisitions.convert', $purchaseRequisition) }}" method="POST" class="d-inline" onsubmit="return confirm('Konversi menjadi purchase order?')">
                @csrf
                <button type="submit" class="btn btn-info btn-sm text-white">
                    <i class="bi bi-arrow-repeat"></i> Konversi ke PO
                </button>
            </form>
        @endif

        <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi Permintaan</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="160" class="text-muted">No. Permintaan</td>
                        <td><strong>{{ $purchaseRequisition->requisition_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pemohon</td>
                        <td>{{ $purchaseRequisition->requester?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Cabang</td>
                        <td>{{ $purchaseRequisition->branch?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>{!! $purchaseRequisition->status_badge !!}</td>
                    </tr>
                    @if($purchaseRequisition->approver)
                    <tr>
                        <td class="text-muted">Disetujui Oleh</td>
                        <td>{{ $purchaseRequisition->approver->name }} @if($purchaseRequisition->approved_at) ({{ $purchaseRequisition->approved_at->format('d/m/Y H:i') }}) @endif</td>
                    </tr>
                    @endif
                    @if($purchaseRequisition->rejection_reason)
                    <tr>
                        <td class="text-muted">Alasan Ditolak</td>
                        <td class="text-danger">{{ $purchaseRequisition->rejection_reason }}</td>
                    </tr>
                    @endif
                    @if($purchaseRequisition->notes)
                    <tr>
                        <td class="text-muted">Catatan</td>
                        <td>{{ $purchaseRequisition->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Item yang Diminta</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th class="text-center">Jumlah</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseRequisition->items as $index => $item)
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
                    <td>{{ $item->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">Tidak ada item.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($purchaseRequisition->status === 'submitted' && $canApprove)
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('purchase-requisitions.reject', $purchaseRequisition) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Permintaan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="rejection_reason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="3" class="form-control" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
