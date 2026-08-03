@extends('layouts.app')
@section('title', 'Daftar Pemasukan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        <h4 class="mb-0">Pemasukan</h4>
        <span class="badge bg-success fs-6">Total: @money($totalAmount)</span>
    </div>
    <a href="{{ route('incomes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Pemasukan</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Dari">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="Sampai">
            </div>
            <div class="col-md-2">
                <select name="payment_method_id" class="form-select">
                    <option value="">Semua Metode</option>
                    @foreach ($paymentMethods as $pm)
                        <option value="{{ $pm->id }}" {{ request('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari...">
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
            <th>Label</th>
            <th>Pelanggan</th>
            <th>Metode</th>
            <th>No. Invoice</th>
            <th class="text-end">Jumlah</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($incomes as $income)
            <tr>
                <td>{{ $income->income_date->format('d/m/Y') }}</td>
                <td>{{ $income->label }}</td>
                <td>{{ $income->customer?->name ?? '-' }}</td>
                <td>{{ $income->paymentMethod?->name ?? '-' }}</td>
                <td>{{ $income->invoice_number }}</td>
                <td class="text-end">@money($income->amount)</td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('incomes.edit', $income) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('incomes.destroy', $income) }}" method="POST" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada pemasukan.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $incomes->links() }}
@endsection
