@extends('layouts.app')
@section('title', 'Detail Poin — ' . $customer->name)
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-stars me-2"></i>{{ $customer->name }}</h4>
    <a href="{{ route('loyalty.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-warning"><div class="card-body text-center">
            <small class="text-muted">Saldo Poin</small>
            <h2 class="text-warning">{{ number_format($customer->loyalty_points) }}</h2>
            <span class="badge bg-info">{{ ucfirst($customer->membership_tier) }}</span>
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card"><div class="card-body">
            <h6>Adjust Poin Manual</h6>
            <form action="{{ route('loyalty.adjust', $customer) }}" method="POST" class="row g-2">
                @csrf
                <div class="col-md-3"><input type="number" name="points" class="form-control" placeholder="Poin (+/-)" required></div>
                <div class="col-md-7"><input type="text" name="description" class="form-control" placeholder="Alasan: bonus, koreksi, redeem manual, dll" required></div>
                <div class="col-md-2"><button class="btn btn-warning w-100"><i class="bi bi-pencil"></i> Adjust</button></div>
            </form>
            <small class="text-muted">Positif untuk tambah, negatif untuk kurangi.</small>
        </div></div>
    </div>
</div>

<div class="card"><div class="card-body">
    <h6 class="mb-3">Histori Transaksi Poin</h6>
    <table class="table table-sm">
        <thead class="table-light"><tr><th>Tanggal</th><th>Tipe</th><th>Deskripsi</th><th class="text-end">Poin</th><th>By</th></tr></thead>
        <tbody>
            @forelse($transactions as $tx)
            <tr>
                <td>{{ $tx->created_at->format('d M Y H:i') }}</td>
                <td>
                    @switch($tx->type)
                        @case('earn')<span class="badge bg-success">Earn</span>@break
                        @case('redeem')<span class="badge bg-warning text-dark">Redeem</span>@break
                        @case('adjust')<span class="badge bg-info">Adjust</span>@break
                        @case('expire')<span class="badge bg-danger">Expire</span>@break
                    @endswitch
                </td>
                <td>{{ $tx->description }}</td>
                <td class="text-end fw-bold {{ $tx->points >= 0 ? 'text-success' : 'text-danger' }}">{{ $tx->points >= 0 ? '+' : '' }}{{ $tx->points }}</td>
                <td class="small">{{ $tx->creator->name ?? 'system' }}</td>
            </tr>
            @empty<tr><td colspan="5" class="text-center text-muted py-3">Belum ada transaksi poin.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $transactions->links() }}
</div></div>
@endsection
