@extends('layouts.app')
@section('title', $branch->name)
@section('content')
@php
    $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0"><i class="fas fa-building me-2"></i>{{ $branch->name }}
            <span class="badge bg-secondary ms-2">{{ $branch->code }}</span>
            @if($branch->is_active)<span class="badge bg-success ms-1">Aktif</span>@else<span class="badge bg-secondary ms-1">Non-Aktif</span>@endif
        </h4>
        <small class="text-muted">{{ $branch->address }}</small>
    </div>
    <div>
        <a href="{{ route('branches.edit', $branch) }}" class="btn btn-warning"><i class="fas fa-edit me-1"></i>Edit</a>
        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-clock me-1"></i>Jam Operasional</strong>
                <a href="{{ route('business-hours.create', ['branch_id' => $branch->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Set Jam
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Hari</th><th>Buka</th><th>Tutup</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($branch->businessHours()->orderBy('day_of_week')->get() as $h)
                        <tr>
                            <td>{{ $days[$h->day_of_week] ?? '?' }}</td>
                            <td>{{ \Carbon\Carbon::parse($h->open_time)->format('H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($h->close_time)->format('H:i') }}</td>
                            <td>
                                @if($h->is_closed)<span class="badge bg-danger">Tutup</span>
                                @else<span class="badge bg-success">Buka</span>@endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('business-hours.edit', $h) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('business-hours.destroy', $h) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada jam operasional.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-calendar-times me-1"></i>Hari Libur</strong>
                <a href="{{ route('holidays.create', ['branch_id' => $branch->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Tambah Libur
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Tanggal</th><th>Nama</th><th>Berulang</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($branch->holidays()->orderBy('date')->get() as $h)
                        <tr>
                            <td>{{ $h->date->format('d M Y') }}</td>
                            <td>{{ $h->name }}</td>
                            <td>
                                @if($h->is_recurring)<span class="badge bg-info">Tahunan</span>
                                @else<span class="badge bg-light text-dark">Sekali</span>@endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('holidays.edit', $h) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('holidays.destroy', $h) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada hari libur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-shower me-1"></i>Washbay / Slot Service</strong>
                <a href="{{ route('washbays.create', ['branch_id' => $branch->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Tambah Washbay
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Nama</th><th>Status</th><th>Service Aktif</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($branch->washbays as $wb)
                        <tr>
                            <td>{{ $wb->name }}</td>
                            <td>
                                @switch($wb->status)
                                    @case('available')<span class="badge bg-success">Kosong</span>@break
                                    @case('in_use')<span class="badge bg-warning text-dark">Dipakai</span>@break
                                    @case('maintenance')<span class="badge bg-danger">Maintenance</span>@break
                                @endswitch
                            </td>
                            <td>{{ $wb->currentService?->job_no ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('washbays.edit', $wb) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('washbays.destroy', $wb) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-3 text-muted">Belum ada washbay.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
