@extends('layouts.app')
@section('title', 'Daftar Penjualan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Penjualan Kendaraan</h4>
    <a href="{{ route('sales.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Penjualan</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Batal</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari pelanggan...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<table class="table table-hover align-middle">
    <thead class="table-light">
        <tr>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Kendaraan</th>
            <th>No. Polisi</th>
            <th class="text-end">Harga</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($sales as $sale)
            <tr>
                <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                <td>{{ $sale->customer->name ?? '-' }}</td>
                <td>{{ $sale->vehicle?->vehicleBrand?->name }} {{ $sale->vehicle?->model_name }}</td>
                <td>{{ $sale->vehicle?->number_plate }}</td>
                <td class="text-end">@money($sale->price)</td>
                <td>
                    @if ($sale->status === 'completed')
                        <span class="badge bg-success">Selesai</span>
                    @elseif ($sale->status === 'cancelled')
                        <span class="badge bg-danger">Batal</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('sales.destroy', $sale) }}" method="POST" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada penjualan.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $sales->links() }}
@endsection
