@extends('layouts.app')
@section('title', 'Kas Kecil (Petty Cash)')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-wallet2 me-2"></i>Kas Kecil (Petty Cash)</h4>
    <div class="card border-0 bg-light px-3 py-2">
        <small class="text-muted">Saldo</small>
        <h4 class="mb-0 text-{{ $balance >= 0 ? 'success' : 'danger' }}">@money($balance)</h4>
    </div>
</div>

<div class="card mb-3"><div class="card-body">
    <h6 class="mb-2">Tambah Transaksi</h6>
    <form action="{{ route('petty-cash.store') }}" method="POST" class="row g-2">
        @csrf
        <div class="col-md-2"><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="col-md-2"><select name="type" class="form-select" required>
            <option value="in">Pemasukan (in)</option>
            <option value="out">Pengeluaran (out)</option>
            <option value="replenish">Replenish (top-up)</option>
        </select></div>
        <div class="col-md-2"><input type="number" name="amount" class="form-control" placeholder="Jumlah" step="0.01" required></div>
        <div class="col-md-4"><input type="text" name="description" class="form-control" placeholder="Deskripsi" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Tambah</button></div>
    </form>
</div></div>

<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2"><select name="type" class="form-select">
            <option value="">Semua Tipe</option>
            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>In</option>
            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Out</option>
            <option value="replenish" {{ request('type') === 'replenish' ? 'selected' : '' }}>Replenish</option>
        </select></div>
        <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Tanggal</th><th>Tipe</th><th>Deskripsi</th><th>Ref</th><th class="text-end">Jumlah</th><th>By</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($rows as $r)
            <tr>
                <td>{{ $r->date->format('d M Y') }}</td>
                <td>
                    @switch($r->type)
                        @case('in')<span class="badge bg-success">In</span>@break
                        @case('out')<span class="badge bg-danger">Out</span>@break
                        @case('replenish')<span class="badge bg-info">Replenish</span>@break
                    @endswitch
                </td>
                <td>{{ $r->description }}</td>
                <td class="small">{{ $r->reference }}</td>
                <td class="text-end fw-bold {{ $r->type === 'out' ? 'text-danger' : 'text-success' }}">
                    {{ $r->type === 'out' ? '-' : '+' }} @money($r->amount)
                </td>
                <td class="small">{{ $r->creator?->name }}</td>
                <td>
                    <form action="{{ route('petty-cash.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-3">Belum ada transaksi kas kecil.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $rows->links() }}
</div></div>
@endsection
