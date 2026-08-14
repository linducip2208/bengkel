@extends('layouts.app')
@section('title', 'Detail Klaim Asuransi')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-file-invoice-dollar me-2"></i>Detail Klaim {{ $insuranceClaim->claim_number }}</h4>
    <div>
        <a href="{{ route('insurance-claims.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Informasi Klaim</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:150px;">No. Klaim</td><td><code>{{ $insuranceClaim->claim_number }}</code></td></tr>
                    <tr><td>Status</td><td>
                        @php
                            $badges = ['pending'=>'secondary','submitted'=>'info','approved'=>'success','rejected'=>'danger','paid'=>'primary'];
                        @endphp
                        <span class="badge bg-{{ $badges[$insuranceClaim->status] ?? 'secondary' }}">{{ ucfirst($insuranceClaim->status) }}</span>
                    </td></tr>
                    <tr><td>Tanggal Klaim</td><td>{{ $insuranceClaim->claim_date?->format('d M Y') }}</td></tr>
                    <tr><td>Customer</td><td>{{ $insuranceClaim->customer?->name ?? '-' }}</td></tr>
                    <tr><td>Kendaraan</td><td>{{ $insuranceClaim->vehicle?->number_plate ?? '-' }}</td></tr>
                    <tr><td>Service</td><td>{{ $insuranceClaim->service?->job_no ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Polis &amp; Nilai</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:150px;">Asuransi</td><td>{{ $insuranceClaim->insurance_company ?? '-' }}</td></tr>
                    <tr><td>No. Polis</td><td>{{ $insuranceClaim->policy_number ?? '-' }}</td></tr>
                    <tr><td>Estimasi</td><td>@money($insuranceClaim->estimated_amount ?? 0)</td></tr>
                    <tr><td>Disetujui</td><td>@money($insuranceClaim->approved_amount ?? 0)</td></tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Catatan</strong></div>
            <div class="card-body"><p class="mb-0">{{ $insuranceClaim->notes ?? '-' }}</p></div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Aksi</strong></div>
            <div class="card-body">
                @if($insuranceClaim->status === 'pending' || $insuranceClaim->status === 'submitted')
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <form action="{{ route('insurance-claims.approve', $insuranceClaim) }}" method="POST" class="d-flex align-items-end gap-2">
                        @csrf
                        <div>
                            <label class="form-label mb-0 small">Nilai Disetujui</label>
                            <input type="number" step="0.01" min="0" name="approved_amount" class="form-control form-control-sm" value="{{ $insuranceClaim->estimated_amount ?? '' }}" style="width:160px;">
                        </div>
                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check me-1"></i>Setujui</button>
                    </form>
                    <form action="{{ route('insurance-claims.reject', $insuranceClaim) }}" method="POST" class="d-flex align-items-end gap-2">
                        @csrf
                        <div>
                            <label class="form-label mb-0 small">Alasan Tolak</label>
                            <input type="text" name="notes" class="form-control form-control-sm" style="width:220px;">
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times me-1"></i>Tolak</button>
                    </form>
                </div>
                @endif
                @if($insuranceClaim->status === 'approved')
                <form action="{{ route('insurance-claims.mark-paid', $insuranceClaim) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-money-bill-wave me-1"></i>Tandai Dibayar</button>
                </form>
                @endif
                <form action="{{ route('insurance-claims.destroy', $insuranceClaim) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus klaim ini?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
