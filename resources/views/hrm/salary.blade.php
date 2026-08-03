@extends('layouts.app')
@section('title', 'Gaji Karyawan')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-cash me-2"></i>Gaji Karyawan</h4>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card mb-3"><div class="card-body">
    <form action="{{ route('hrm.salary.generate') }}" method="POST" class="row g-2">
        @csrf
        <div class="col-md-3"><select name="period_month" class="form-select">
            @foreach(range(1,12) as $m)<option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endforeach
        </select></div>
        <div class="col-md-2"><input type="number" name="period_year" class="form-control" value="{{ $year }}" min="2020" max="2100"></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-gear me-1"></i>Generate Gaji Periode Ini</button></div>
    </form>
    <small class="text-muted">Sistem auto-hitung dari: base salary (di user profile) + komisi service periode ini.</small>
</div></div>

<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2"><select name="month" class="form-select">
            @foreach(range(1,12) as $m)<option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endforeach
        </select></div>
        <div class="col-md-2"><input type="number" name="year" class="form-control" value="{{ request('year', $year) }}"></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Karyawan</th><th>Hadir/Absen</th><th class="text-end">Gaji Pokok</th><th class="text-end">Komisi</th><th class="text-end">Net</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($salaries as $s)
            <tr>
                <td><strong>{{ $s->user->name }}</strong><br><small class="text-muted">{{ $s->user->position }}</small></td>
                <td>{{ $s->days_present }}/{{ $s->days_absent }}</td>
                <td class="text-end">@money($s->base_salary)</td>
                <td class="text-end text-warning">@money($s->commission_total)</td>
                <td class="text-end fw-bold">@money($s->net_salary)</td>
                <td>@if($s->status === 'paid')<span class="badge bg-success">Paid {{ $s->paid_at?->format('d/m') }}</span>@else<span class="badge bg-warning text-dark">Draft</span>@endif</td>
                <td>
                    <a href="{{ route('hrm.salary.slip', $s) }}" class="btn btn-sm btn-info" title="Print Slip PDF"><i class="bi bi-file-pdf"></i></a>
                    @if($s->status !== 'paid')
                    <form action="{{ route('hrm.salary.mark-paid', $s) }}" method="POST" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-success" title="Tandai Dibayar"><i class="bi bi-check2"></i></button></form>
                    @endif
                </td>
            </tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-3">Belum ada gaji untuk periode ini. Klik <em>Generate</em> di atas.</td></tr>@endforelse
        </tbody>
    </table>
</div></div>
@endsection
