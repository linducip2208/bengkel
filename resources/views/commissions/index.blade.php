@extends('layouts.app')
@section('title', 'Komisi Teknisi')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Komisi Teknisi</h4>
    <a href="{{ route('commissions.report') }}" class="btn btn-outline-primary"><i class="bi bi-bar-chart me-1"></i>Laporan</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-warning"><div class="card-body text-center">
            <h4 class="text-warning">@money($summary['total_unpaid'])</h4>
            <small class="text-muted">Total Komisi Belum Dibayar</small>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-success"><div class="card-body text-center">
            <h4 class="text-success">@money($summary['total_paid_month'])</h4>
            <small class="text-muted">Sudah Dibayar Bulan Ini</small>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-info"><div class="card-body text-center">
            <h4 class="text-info">{{ $summary['pending_count'] }}</h4>
            <small class="text-muted">Service Pending Bayar Komisi</small>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="user_id" class="form-select">
                    <option value="">Semua Teknisi</option>
                    @foreach($technicians as $t)
                        <option value="{{ $t->id }}" {{ request('user_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Sudah Dibayar</option>
                </select>
            </div>
            <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </form>

        <form action="{{ route('commissions.markPaidBatch') }}" method="POST">
            @csrf
            <div class="d-flex justify-content-between mb-2">
                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Tandai semua yang dicentang sebagai sudah dibayar?')">
                    <i class="bi bi-check2-all me-1"></i>Tandai Dibayar (Batch)
                </button>
                <small class="text-muted">Klik checkbox lalu klik tombol batch</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle small">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll"></th>
                            <th>Tanggal Service</th><th>Service</th><th>Teknisi</th><th>Role</th><th class="text-end">Charge</th><th class="text-end">%</th><th class="text-end">Komisi</th><th>Status</th><th>Dibayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                        <tr>
                            <td>
                                @if(!$r->paid_at)<input type="checkbox" name="ids[]" value="{{ $r->id }}" class="row-check">@endif
                            </td>
                            <td>{{ $r->service->service_date->format('d M Y') }}</td>
                            <td>
                                <strong>{{ $r->service->job_no }}</strong><br>
                                <small class="text-muted">{{ $r->service->customer->name ?? '-' }} • {{ $r->service->vehicle->number_plate ?? '-' }}</small>
                            </td>
                            <td>{{ $r->user->name ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $r->role ?? 'lead' }}</span></td>
                            <td class="text-end">@money($r->service->charge)</td>
                            <td class="text-end">{{ number_format((float) $r->commission_pct, 1) }}%</td>
                            <td class="text-end"><strong>@money($r->commission_amt ?? 0)</strong></td>
                            <td>
                                @if($r->paid_at)
                                    <span class="badge bg-success">Dibayar</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($r->paid_at)
                                    <small>{{ $r->paid_at->format('d/m H:i') }}<br>{{ $r->paidBy->name ?? '' }}</small>
                                @else
                                    <form action="{{ route('commissions.markPaid', $r) }}" method="POST" class="d-inline">
                                        @csrf @method('PUT')
                                        <button class="btn btn-sm btn-success" title="Bayar"><i class="bi bi-check2"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-3 text-muted">Belum ada data komisi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
        <div class="d-flex justify-content-end">{{ $rows->links() }}</div>
    </div>
</div>

<script>
    document.getElementById('selectAll')?.addEventListener('change', function(e) {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = e.target.checked);
    });
</script>
@endsection
