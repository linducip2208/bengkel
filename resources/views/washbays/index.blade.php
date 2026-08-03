@extends('layouts.app')
@section('title', 'Washbay')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-shower me-2"></i>Washbay / Slot Service</h4>
    <a href="{{ route('washbays.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Washbay</a>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="branch_id" class="form-select">
            <option value="">Semua Cabang</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">Semua Status</option>
            <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Kosong</option>
            <option value="in_use" {{ request('status') === 'in_use' ? 'selected' : '' }}>Dipakai</option>
            <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
    </div>
</form>

<div class="row g-3">
    @forelse($washbays as $wb)
    <div class="col-md-4 col-lg-3">
        <div class="card h-100
            @if($wb->status === 'available') border-success
            @elseif($wb->status === 'in_use') border-warning
            @else border-danger
            @endif">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="card-title mb-1">{{ $wb->name }}</h6>
                    @switch($wb->status)
                        @case('available')<span class="badge bg-success">Kosong</span>@break
                        @case('in_use')<span class="badge bg-warning text-dark">Dipakai</span>@break
                        @case('maintenance')<span class="badge bg-danger">Maintenance</span>@break
                    @endswitch
                </div>
                <small class="text-muted">{{ $wb->branch->name ?? 'Tanpa cabang' }}</small>

                @if($wb->currentService)
                <hr class="my-2">
                <div class="small">
                    <div><strong>{{ $wb->currentService->job_no }}</strong></div>
                    <div>{{ $wb->currentService->customer->name ?? '-' }}</div>
                    <div class="text-muted">{{ $wb->currentService->vehicle->number_plate ?? '-' }}</div>
                </div>
                @endif

                <div class="mt-2">
                    @if($wb->status === 'in_use')
                        <form action="{{ route('washbays.release', $wb) }}" method="POST" class="d-inline" onsubmit="return confirm('Kosongkan washbay ini?')">
                            @csrf
                            <button class="btn btn-sm btn-outline-success"><i class="fas fa-sign-out-alt me-1"></i>Kosongkan</button>
                        </form>
                    @endif
                    <a href="{{ route('washbays.edit', $wb) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('washbays.destroy', $wb) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus washbay ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card"><div class="card-body text-center text-muted py-5">
            Belum ada washbay. <a href="{{ route('washbays.create') }}">Tambah washbay pertama</a>.
        </div></div>
    </div>
    @endforelse
</div>
@endsection
