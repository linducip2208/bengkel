@extends('layouts.app')
@section('title', 'Retur Penjualan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Retur Penjualan</h4>
    <a href="{{ route('sell-returns.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Buat Retur
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('sell-returns.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari no. / pelanggan..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
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
                    <th>No. Retur</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th class="text-center">Item</th>
                    <th class="text-end">Refund</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $sellReturn)
                <tr>
                    <td>
                        <a href="{{ route('sell-returns.show', $sellReturn) }}" class="text-decoration-none">
                            <strong>{{ $sellReturn->return_number }}</strong>
                        </a>
                    </td>
                    <td>{{ $sellReturn->return_date->format('d/m/Y') }}</td>
                    <td>{{ $sellReturn->customer?->name ?? '-' }}</td>
                    <td class="text-center">{{ $sellReturn->items->count() }}</td>
                    <td class="text-end">@money($sellReturn->refund_amount)</td>
                    <td>{!! $sellReturn->status_badge !!}</td>
                    <td class="text-center">
                        <a href="{{ route('sell-returns.show', $sellReturn) }}" class="btn btn-sm btn-outline-info" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Tidak ada retur penjualan ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">{{ $returns->total() }} retur</small>
        {{ $returns->withQueryString()->links() }}
    </div>
</div>
@endsection
