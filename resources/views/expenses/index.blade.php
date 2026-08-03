@extends('layouts.app')
@section('title', 'Daftar Pengeluaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Pengeluaran</h4>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Pengeluaran</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-4">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="Dari">
            </div>
            <div class="col-md-4">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="Sampai">
            </div>
            <div class="col-md-2">
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari label...">
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
            <th>Deskripsi</th>
            <th class="text-end">Jumlah</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($expenses as $expense)
            <tr>
                <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                <td>{{ $expense->label }}</td>
                <td>{{ Str::limit($expense->description, 60) }}</td>
                <td class="text-end text-danger">@money($expense->amount)</td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada pengeluaran.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $expenses->links() }}
@endsection
