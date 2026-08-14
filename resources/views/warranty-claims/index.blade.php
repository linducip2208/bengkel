@extends('layouts.app')
@section('title', 'Klaim Garansi')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-shield-check me-2"></i>Klaim Garansi</h4>
    <a href="{{ route('warranty-claims.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Klaim</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3"><select name="status" class="form-select">
            <option value="">Semua Status</option>
            <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
        </select></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    <div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Tgl Klaim</th><th>Customer</th><th>Produk</th><th>Invoice</th><th>Keluhan</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($claims as $c)
            <tr>
                <td>{{ $c->claim_date->format('d M Y') }}</td>
                <td>{{ $c->customer?->name ?? '-' }}</td>
                <td><a href="{{ route('warranty-claims.show', $c) }}">{{ $c->invoiceItem?->product?->name ?? $c->invoiceItem?->description }}</a></td>
                <td><code class="small">{{ $c->invoiceItem?->invoice?->invoice_number }}</code></td>
                <td><small>{{ Str::limit($c->complaint, 80) }}</small></td>
                <td>
                    <form action="{{ route('warranty-claims.update', $c) }}" method="POST">
                        @csrf @method('PUT')
                        <select name="status" class="form-select form-select-sm" style="width: 130px;" onchange="this.form.submit()">
                            <option value="submitted" {{ $c->status === 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="approved" {{ $c->status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ $c->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="resolved" {{ $c->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </form>
                </td>
                <td>
                    <a href="{{ route('warranty-claims.edit', $c) }}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                    <form action="{{ route('warranty-claims.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>
                </td>
            </tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-3">Belum ada klaim.</td></tr>@endforelse
        </tbody>
    </table>
    </div>
    {{ $claims->links() }}
</div></div>
@endsection
