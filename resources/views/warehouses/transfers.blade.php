@extends('layouts.app')
@section('title', 'Transfer Stok')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Transfer Stok</h4>
    <a href="{{ route('warehouses.transfers.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Transfer Baru</a>
</div>
<div class="card"><div class="card-body p-0">
<table class="table table-hover mb-0"><thead><tr><th>No Transfer</th><th>Dari</th><th>Ke</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>
    @forelse($transfers as $tf)
    <tr>
        <td><code>{{ $tf->transfer_number }}</code></td>
        <td>{{ $tf->fromWarehouse->name ?? '-' }}</td>
        <td>{{ $tf->toWarehouse->name ?? '-' }}</td>
        <td><span class="badge bg-success">{{ $tf->status }}</span></td>
        <td>{{ $tf->created_at->format('d/m/Y H:i') }}</td>
    </tr>
    @empty
    <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada transfer.</td></tr>
    @endforelse
</tbody></table></div></div>
{{ $transfers->links() }}
@endsection
