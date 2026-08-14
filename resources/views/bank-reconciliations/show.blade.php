@extends('layouts.app')
@section('title', 'Detail Rekonsiliasi Bank')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-balance-scale me-2"></i>Detail Rekonsiliasi Bank</h4>
    <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Informasi</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:160px;">Rekening</td><td>{{ $bankReconciliation->bankAccount?->name ?? '-' }} ({{ $bankReconciliation->bankAccount?->bank_name ?? '-' }})</td></tr>
                    <tr><td>No. Rekening</td><td>{{ $bankReconciliation->bankAccount?->account_number ?? '-' }}</td></tr>
                    <tr><td>Periode</td><td>{{ $bankReconciliation->start_date?->format('d M Y') }} — {{ $bankReconciliation->end_date?->format('d M Y') }}</td></tr>
                    <tr><td>Status</td><td><span class="badge bg-{{ $bankReconciliation->status === 'completed' ? 'success' : 'secondary' }}">{{ ucfirst($bankReconciliation->status) }}</span></td></tr>
                    <tr><td>Dibuat Oleh</td><td>{{ $bankReconciliation->creator?->name ?? '-' }}</td></tr>
                </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><strong>Perhitungan</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tr><td>Saldo Awal</td><td class="text-end">@money($bankReconciliation->opening_balance)</td></tr>
                    <tr><td>Total Income (+)</td><td class="text-end text-success">@money($income)</td></tr>
                    <tr><td>Total Expense (−)</td><td class="text-end text-danger">@money($expense)</td></tr>
                    <tr class="table-light"><td><strong>Saldo Akhir (Sistem)</strong></td><td class="text-end"><strong>@money($bankReconciliation->closing_balance)</strong></td></tr>
                    <tr><td>Saldo Rekening Koran</td><td class="text-end">@money($bankReconciliation->statement_balance)</td></tr>
                    <tr class="table-light">
                        <td><strong>Selisih</strong></td>
                        <td class="text-end {{ ($bankReconciliation->difference ?? 0) == 0 ? 'text-success' : 'text-danger' }}"><strong>@money($bankReconciliation->difference)</strong></td>
                    </tr>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Catatan</strong></div>
            <div class="card-body"><p class="mb-0">{{ $bankReconciliation->notes ?? '-' }}</p></div>
        </div>
    </div>
</div>
@endsection
