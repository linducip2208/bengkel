@extends('layouts.app')

@section('title', 'Neraca Saldo (Trial Balance)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="fas fa-scale-balanced me-2"></i>Neraca Saldo</h4>
        <small class="text-muted">Total debit harus sama dengan total kredit.</small>
    </div>
    <form method="GET" action="{{ route('reports.trial-balance') }}" class="d-flex gap-2 flex-wrap">
        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $start }}">
        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $end }}">
        <button class="btn btn-sm btn-primary">Terapkan</button>
        <a href="{{ route('reports.export-pdf', ['type' => 'trial-balance', 'start_date' => $start, 'end_date' => $end]) }}"
           target="_blank"
           @cannot('any-role', ['super_admin','admin','manager']) hidden @endcannot
           class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i>PDF</a>
    </form>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Periode {{ \Carbon\Carbon::parse($start)->format('d M Y') }} — {{ \Carbon\Carbon::parse($end)->format('d M Y') }}</strong>
        <span class="badge {{ $balanced ? 'bg-success' : 'bg-danger' }} fs-6">
            {{ $balanced ? 'SEIMBANG ✓' : 'TIDAK SEIMBANG ✗' }}
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Nama Akun</th>
                    <th>Tipe</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Kredit</th>
                    <th class="text-end">Saldo (D−K)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activeAccounts as $account)
                    <tr>
                        <td class="font-monospace">{{ $account->code }}</td>
                        <td>{{ $account->name }}</td>
                        <td><span class="badge bg-secondary">{{ $account->type }}</span></td>
                        <td class="text-end font-monospace">Rp {{ number_format($account->total_debit, 2, ',', '.') }}</td>
                        <td class="text-end font-monospace">Rp {{ number_format($account->total_credit, 2, ',', '.') }}</td>
                        <td class="text-end font-monospace fw-bold">Rp {{ number_format($account->net, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                            Tidak ada transaksi jurnal pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($activeAccounts->isNotEmpty())
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="3" class="text-end">TOTAL</td>
                    <td class="text-end font-monospace">Rp {{ number_format($totalDebit, 2, ',', '.') }}</td>
                    <td class="text-end font-monospace">Rp {{ number_format($totalCredit, 2, ',', '.') }}</td>
                    <td class="text-end font-monospace">Rp {{ number_format($totalDebit - $totalCredit, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
