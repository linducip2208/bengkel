@extends('layouts.app')
@section('title', 'Budget')
@section('content')
@php
    $revenueBudget = $budgets->where('category', 'revenue')->sum('amount');
    $expenseBudget = $budgets->where('category', 'expense')->sum('amount');
    $revenueActual = 0; $expenseActual = 0;
    foreach ($budgets as $b) {
        $key = ($b->branch_id ?? 'all') . '|' . $b->category;
        if ($b->category === 'revenue') { $revenueActual += $actualMap[$key] ?? 0; }
        else { $expenseActual += $actualMap[$key] ?? 0; }
    }
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Budget</h4>
    <a href="{{ route('budgets.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Budget</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary"><div class="card-body">
            <div class="small">Budget Pendapatan</div>
            <div class="fs-4 fw-bold">@money($revenueBudget)</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success"><div class="card-body">
            <div class="small">Realisasi Pendapatan</div>
            <div class="fs-4 fw-bold">@money($revenueActual)</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning"><div class="card-body">
            <div class="small">Budget Pengeluaran</div>
            <div class="fs-4 fw-bold">@money($expenseBudget)</div>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-danger"><div class="card-body">
            <div class="small">Realisasi Pengeluaran</div>
            <div class="fs-4 fw-bold">@money($expenseActual)</div>
        </div></div>
    </div>
</div>

<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="period" class="form-select" onchange="this.form.submit()">
                @foreach($periods as $p)
                    <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="branch_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Cabang</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Cabang</th>
                    <th>Kategori</th>
                    <th>Periode</th>
                    <th class="text-end">Budget</th>
                    <th class="text-end">Realisasi</th>
                    <th class="text-end">Selisih</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budgets as $budget)
                @php
                    $key = ($budget->branch_id ?? 'all') . '|' . $budget->category;
                    $actual = $actualMap[$key] ?? 0;
                    $variance = $actual - $budget->amount;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $budget->branch?->name ?? 'Semua Cabang' }}</td>
                    <td>
                        @if($budget->category === 'revenue')<span class="badge bg-success">Revenue</span>
                        @else<span class="badge bg-warning text-dark">Expense</span>@endif
                    </td>
                    <td>{{ $budget->period }}</td>
                    <td class="text-end">@money($budget->amount)</td>
                    <td class="text-end">@money($actual)</td>
                    <td class="text-end {{ $variance >= 0 ? ($budget->category === 'expense' ? 'text-danger' : 'text-success') : ($budget->category === 'expense' ? 'text-success' : 'text-danger') }}">@money($variance)</td>
                    <td class="text-end">
                        <a href="{{ route('budgets.edit', $budget) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('budgets.destroy', $budget) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus budget ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-3 text-muted">Belum ada budget untuk periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div></div>
@endsection
