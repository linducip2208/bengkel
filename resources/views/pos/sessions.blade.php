@extends('layouts.app')
@section('title', 'Histori Sesi POS')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i>Histori Sesi POS</h4>
    <a href="{{ route('pos.terminal') }}" class="btn btn-primary"><i class="bi bi-cash-stack me-1"></i>Buka Terminal POS</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Buka</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Tutup</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr><th>ID</th><th>Dibuka</th><th>Ditutup</th><th>Kasir</th><th>Cabang</th><th class="text-end">Saldo Awal</th><th class="text-end">Revenue</th><th class="text-end">Saldo Akhir</th><th class="text-end">Selisih</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($sessions as $s)
                    <tr>
                        <td>#{{ $s->id }}</td>
                        <td>{{ $s->opened_at->format('d M H:i') }}</td>
                        <td>{{ $s->closed_at?->format('d M H:i') ?? '-' }}</td>
                        <td>{{ $s->user->name ?? '-' }}</td>
                        <td>{{ $s->branch->name ?? 'Pusat' }}</td>
                        <td class="text-end">@money($s->opening_balance)</td>
                        <td class="text-end">@money($s->revenue)</td>
                        <td class="text-end">{{ $s->closing_balance !== null ? \App\Models\Currency::format($s->closing_balance) : '-' }}</td>
                        <td class="text-end {{ $s->difference == 0 ? 'text-success' : ($s->difference > 0 ? 'text-info' : 'text-danger') }}">
                            {{ $s->difference !== null ? \App\Models\Currency::format($s->difference) : '-' }}
                        </td>
                        <td>
                            @if($s->status === 'open')<span class="badge bg-success">Buka</span>
                            @else<span class="badge bg-secondary">Tutup</span>@endif
                        </td>
                        <td>
                            @if($s->status === 'open')
                                <a href="{{ route('pos.closeForm', $s) }}" class="btn btn-sm btn-warning" title="Tutup Sesi"><i class="bi bi-lock"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="11" class="text-center py-3 text-muted">Belum ada sesi POS.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">{{ $sessions->links() }}</div>
    </div>
</div>
@endsection
