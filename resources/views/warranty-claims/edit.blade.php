@extends('layouts.app')
@section('title', 'Edit Klaim Garansi')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-shield-check me-2"></i>Edit Klaim Garansi #{{ $warrantyClaim->id }}</h4>
    <a href="{{ route('warranty-claims.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<div class="card"><div class="card-body">
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="table-responsive">
            <table class="table table-sm table-borderless">
                <tr><td style="width:120px;"><strong>Customer</strong></td><td>{{ $warrantyClaim->customer?->name ?? '-' }}</td></tr>
                <tr><td><strong>Produk</strong></td><td>{{ $warrantyClaim->invoiceItem?->product?->name ?? $warrantyClaim->invoiceItem?->description ?? '-' }}</td></tr>
                <tr><td><strong>Invoice</strong></td><td><code>{{ $warrantyClaim->invoiceItem?->invoice?->invoice_number }}</code></td></tr>
                <tr><td><strong>Tgl Klaim</strong></td><td>{{ $warrantyClaim->claim_date->format('d M Y') }}</td></tr>
                <tr><td><strong>Keluhan</strong></td><td>{{ $warrantyClaim->complaint }}</td></tr>
            </table>
            </div>
        </div>
    </div>
    <hr>
    <form action="{{ route('warranty-claims.update', $warrantyClaim) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="submitted" {{ $warrantyClaim->status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                    <option value="approved" {{ $warrantyClaim->status === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ $warrantyClaim->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="resolved" {{ $warrantyClaim->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">Resolusi / Catatan</label>
                <textarea name="resolution" class="form-control @error('resolution') is-invalid @enderror" rows="4" placeholder="Tindakan yang diambil, keputusan garansi...">{{ old('resolution', $warrantyClaim->resolution) }}</textarea>
                @error('resolution')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Simpan Perubahan</button>
    </form>
</div></div>
@endsection
