@extends('layouts.app')
@section('title', 'Bank Accounts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-university me-2"></i>Bank Accounts</h4>
    <a href="{{ route('bank-accounts.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah</a>
</div>
<div class="card"><div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Bank</th><th>No. Rekening</th><th>Pemilik</th><th class="text-end">Saldo</th><th>Cabang</th><th class="text-center">Aktif</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
                @forelse($bankAccounts as $account)
                <tr>
                    <td>{{ $loop->iteration + $bankAccounts->firstItem() - 1 }}</td>
                    <td>{{ $account->name }}</td>
                    <td>{{ $account->bank_name }}</td>
                    <td>{{ $account->account_number }}</td>
                    <td>{{ $account->account_holder }}</td>
                    <td class="text-end">{{ \App\Models\Currency::format($account->current_balance) }}</td>
                    <td>{{ $account->branch?->name ?? '—' }}</td>
                    <td class="text-center">@if($account->is_active)<span class="badge bg-success">Aktif</span>@else<span class="badge bg-secondary">Off</span>@endif</td>
                    <td class="text-end">
                        <a href="{{ route('bank-accounts.edit', $account) }}" class="btn btn-sm btn-outline-warning me-1"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('bank-accounts.destroy', $account) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-3 text-muted">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $bankAccounts->links() }}</div>
</div></div>
@endsection
