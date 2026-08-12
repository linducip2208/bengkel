@extends('layouts.app')
@section('title', 'Permintaan Pembelian')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Permintaan Pembelian</h4>
    <a href="{{ route('purchase-requisitions.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Buat Permintaan
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase-requisitions.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari no. / pemohon..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Diajukan</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    <option value="converted" {{ request('status') == 'converted' ? 'selected' : '' }}>Dikonversi</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>No. Permintaan</th>
                    <th>Pemohon</th>
                    <th class="text-center">Item</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requisitions as $requisition)
                <tr>
                    <td>
                        <a href="{{ route('purchase-requisitions.show', $requisition) }}" class="text-decoration-none">
                            <strong>{{ $requisition->requisition_number }}</strong>
                        </a>
                        <div class="text-muted small">{{ $requisition->created_at->format('d/m/Y H:i') }}</div>
                    </td>
                    <td>{{ $requisition->requester?->name ?? '-' }}</td>
                    <td class="text-center">{{ $requisition->items_count }}</td>
                    <td>{!! $requisition->status_badge !!}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('purchase-requisitions.show', $requisition) }}" class="btn btn-outline-info" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            @if($requisition->status === 'draft')
                            <form action="{{ route('purchase-requisitions.destroy', $requisition) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus permintaan ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Tidak ada permintaan pembelian ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $requisitions->total() }} permintaan</small>
        {{ $requisitions->withQueryString()->links() }}
    </div>
</div>
@endsection
