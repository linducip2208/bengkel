@extends('layouts.app')
@section('title', 'Laporan Komisi Teknisi')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Laporan Komisi Teknisi</h4>
    <a href="{{ route('commissions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3 no-print">
            <div class="col-md-3"><input type="date" name="date_from" value="{{ $from }}" class="form-control"></div>
            <div class="col-md-3"><input type="date" name="date_to" value="{{ $to }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </form>
        <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light"><tr><th>#</th><th>Teknisi</th><th class="text-end">Service Selesai</th><th class="text-end">Total Komisi</th><th class="text-end">Belum Dibayar</th><th class="text-end">Sudah Dibayar</th></tr></thead>
            <tbody>
                @forelse($rows as $idx => $r)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td><strong>{{ $r->technician }}</strong></td>
                    <td class="text-end">{{ $r->service_count }}</td>
                    <td class="text-end"><strong>@money($r->total_commission ?? 0)</strong></td>
                    <td class="text-end text-warning">@money($r->unpaid ?? 0)</td>
                    <td class="text-end text-success">@money(($r->total_commission ?? 0) - ($r->unpaid ?? 0))</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3 text-muted">Tidak ada data komisi di periode ini.</td></tr>
                @endforelse
            </tbody>
            @if($rows->count() > 0)
            <tfoot class="table-light">
                <tr>
                    <th colspan="2">TOTAL</th>
                    <th class="text-end">{{ $rows->sum('service_count') }}</th>
                    <th class="text-end">@money($rows->sum('total_commission'))</th>
                    <th class="text-end text-warning">@money($rows->sum('unpaid'))</th>
                    <th class="text-end text-success">@money($rows->sum('total_commission') - $rows->sum('unpaid'))</th>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>
</div>
@endsection
