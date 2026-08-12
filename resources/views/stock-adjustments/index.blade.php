@extends('layouts.app')
@section('title', 'Stock Adjustment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Stock Adjustment</h4>
    <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Request Adjustment
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('stock-adjustments.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="product_search" class="form-control form-control-sm" placeholder="Cari produk..." value="{{ request('product_search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('stock-adjustments.index') }}" class="btn btn-sm btn-outline-danger w-100">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Produk</th>
                        <th>Gudang</th>
                        <th>Stok Awal</th>
                        <th>Stok Baru</th>
                        <th>Selisih</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Request By</th>
                        <th>Approved By</th>
                        <th>Tanggal</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $adj)
                    <tr>
                        <td>#{{ $adj->id }}</td>
                        <td>{{ $adj->product?->name ?? '-' }}</td>
                        <td>{{ $adj->warehouse?->name ?? '-' }}</td>
                        <td>{{ $adj->previous_quantity }}</td>
                        <td>{{ $adj->new_quantity }}</td>
                        <td>
                            @if($adj->quantity_change > 0)
                                <span class="text-success">+{{ $adj->quantity_change }}</span>
                            @elseif($adj->quantity_change < 0)
                                <span class="text-danger">{{ $adj->quantity_change }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($adj->reason, 40) }}</td>
                        <td>
                            @if($adj->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($adj->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                        <td>{{ $adj->requestedBy?->name ?? '-' }}</td>
                        <td>{{ $adj->approvedBy?->name ?? '-' }}</td>
                        <td>{{ $adj->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            @if($adj->status === 'pending')
                                <div class="d-flex gap-1 justify-content-end">
                                    <form method="POST" action="{{ route('stock-adjustments.approve', $adj) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Approve" onclick="return confirm('Approve adjustment ini? Stok akan diubah.')">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Reject" onclick="document.getElementById('rejectModal{{ $adj->id }}').classList.add('d-block')" style="display:inline-block;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>

                                <div class="modal fade" id="rejectModal{{ $adj->id }}" tabindex="-1" style="display:none;">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('stock-adjustments.reject', $adj) }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Reject Adjustment #{{ $adj->id }}</h6>
                                                    <button type="button" class="btn-close" onclick="document.getElementById('rejectModal{{ $adj->id }}').classList.remove('d-block')"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                                                        <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Jelaskan alasan penolakan..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('rejectModal{{ $adj->id }}').classList.remove('d-block')">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @else
                                @if($adj->rejection_reason)
                                    <small class="text-muted d-block">{{ Str::limit($adj->rejection_reason, 30) }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size:2rem;"></i>
                            <p class="mt-2 mb-0">Tidak ada data stock adjustment.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($adjustments->hasPages())
    <div class="card-footer">
        {{ $adjustments->links() }}
    </div>
    @endif
</div>
@endsection
