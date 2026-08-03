@extends('layouts.app')
@section('title', 'Absensi Karyawan')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-calendar-check me-2"></i>Absensi Karyawan</h4>
    <div>
        <form action="{{ route('hrm.clock-in') }}" method="POST" class="d-inline">@csrf<button class="btn btn-success"><i class="bi bi-box-arrow-in-right me-1"></i>Clock In Saya</button></form>
        <form action="{{ route('hrm.clock-out') }}" method="POST" class="d-inline">@csrf<button class="btn btn-warning"><i class="bi bi-box-arrow-right me-1"></i>Clock Out Saya</button></form>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="card"><div class="card-body">
    <form method="GET" class="mb-3"><div class="input-group" style="max-width: 240px;">
        <input type="month" name="month" value="{{ $month }}" class="form-control">
        <button class="btn btn-outline-secondary"><i class="bi bi-funnel"></i></button>
    </div></form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle small">
            <thead class="table-light">
                <tr><th>Karyawan</th>
                    @for($d = 1; $d <= \Carbon\Carbon::create($year, $monthNum)->daysInMonth; $d++)
                    <th class="text-center">{{ $d }}</th>
                    @endfor
                    <th class="text-center">Hadir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td class="fw-bold">{{ $u->name }}</td>
                    @php $userAtts = $attendances->get($u->id, collect())->keyBy(fn($a) => $a->date->day); $present = 0; @endphp
                    @for($d = 1; $d <= \Carbon\Carbon::create($year, $monthNum)->daysInMonth; $d++)
                    @php $att = $userAtts->get($d); if ($att && in_array($att->status, ['present','late'])) $present++; @endphp
                    <td class="text-center">
                        @if($att)
                            @switch($att->status)
                                @case('present')<i class="bi bi-check-circle-fill text-success" title="Hadir {{ $att->clock_in }}"></i>@break
                                @case('late')<i class="bi bi-exclamation-circle-fill text-warning" title="Telat {{ $att->clock_in }}"></i>@break
                                @case('absent')<i class="bi bi-x-circle-fill text-danger"></i>@break
                                @case('leave')<i class="bi bi-airplane text-info"></i>@break
                                @case('sick')<i class="bi bi-heart-pulse text-secondary"></i>@break
                            @endswitch
                        @endif
                    </td>
                    @endfor
                    <td class="text-center fw-bold">{{ $present }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <small class="text-muted">
        <i class="bi bi-check-circle-fill text-success"></i> Hadir •
        <i class="bi bi-exclamation-circle-fill text-warning"></i> Telat •
        <i class="bi bi-x-circle-fill text-danger"></i> Absen •
        <i class="bi bi-airplane text-info"></i> Cuti •
        <i class="bi bi-heart-pulse text-secondary"></i> Sakit
    </small>
</div></div>
@endsection
