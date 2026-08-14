@extends('layouts.app')
@section('title', 'Detail Pengeluaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Pengeluaran</h4>
    <div>
        <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <td width="180" class="text-muted">Label</td>
                <td><strong>{{ $expense->label }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Tanggal</td>
                <td>{{ $expense->expense_date->format('d F Y') }}</td>
            </tr>
            <tr>
                <td class="text-muted">Jumlah</td>
                <td><strong class="text-danger">@money($expense->amount)</strong></td>
            </tr>
            @if($expense->description)
            <tr>
                <td class="text-muted">Deskripsi</td>
                <td>{{ $expense->description }}</td>
            </tr>
            @endif
        </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@endsection
