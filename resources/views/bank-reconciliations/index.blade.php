@extends('layouts.app')
@section('title', 'Bank Reconciliation')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Bank Reconciliation</h4>
    <a href="{{ route('bank-reconciliations.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Rekonsiliasi Baru</a>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4">
            <select name="bank_account_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Rekening</option>
                @foreach($bankAccounts as $account)
                    <option value="{{ $account->id }}" {{ request('bank_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }} ({{ $account->bank_name }})</option>
                @endforeach
            </select>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Rekening</th>
                    <th>Periode</th>
                    <th class="text-end">Saldo Awal</th>
                    <th class="text-end">Saldo Akhir</th>
                    <th class="text-end">Saldo Rekening Koran</th>
                    <th class="text-end">Selisih</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reconciliations as $r)
                <tr>
                    <td>{{ $loop->iteration + $reconciliations->firstItem() - 1 }}</td>
                    <td>{{ $r->bankAccount?->name ?? '-' }}</td>
                    <td>{{ $r->start_date?->format('d M Y') }} — {{ $r->end_date?->format('d M Y') }}</td>
                    <td class="text-end">@money($r->opening_balance)</td>
                    <td class="text-end">@money($r->closing_balance)</td>
                    <td class="text-end">@money($r->statement_balance)</td>
                    <td class="text-end {{ ($r->difference ?? 0) == 0 ? 'text-success' : 'text-danger' }}">@money($r->difference)</td>
                    <td>
                        <span class="badge bg-{{ $r->status === 'completed' ? 'success' : 'secondary' }}">{{ ucfirst($r->status) }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('bank-reconciliations.show', $r) }}" class="btn btn-sm btn-outline-primary" title="Detail"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-3 text-muted">Belum ada data rekonsiliasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $reconciliations->links() }}</div>
</div></div>
@endsection
