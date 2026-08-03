@extends('layouts.app')
@section('title', 'Booking Customer')
@section('content')
<div class="d-flex justify-content-between mb-4"><h4><i class="bi bi-calendar-check me-2"></i>Booking Customer (Online)</h4>
    <a href="{{ route('public.booking') }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Lihat Form Publik</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-warning"><div class="card-body text-center"><h3 class="text-warning">{{ $summary['pending'] }}</h3><small class="text-muted">Pending Konfirmasi</small></div></div></div>
    <div class="col-md-4"><div class="card border-info"><div class="card-body text-center"><h3 class="text-info">{{ $summary['today'] }}</h3><small class="text-muted">Hari Ini</small></div></div></div>
    <div class="col-md-4"><div class="card border-success"><div class="card-body text-center"><h3 class="text-success">{{ $summary['this_week'] }}</h3><small class="text-muted">Minggu Ini</small></div></div></div>
</div>

<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2"><select name="status" class="form-select">
            <option value="">Semua Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>Dikerjakan</option>
            <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Selesai</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Batal</option>
        </select></div>
        <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i> Filter</button></div>
    </form>
    <table class="table table-hover align-middle">
        <thead class="table-light"><tr><th>Tanggal</th><th>Customer</th><th>Kontak</th><th>Kendaraan</th><th>Keluhan</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
            @forelse($bookings as $b)
            <tr>
                <td>{{ $b->booking_at->format('d M Y H:i') }}</td>
                <td><strong>{{ $b->name }}</strong></td>
                <td><a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $b->phone)) }}" target="_blank">{{ $b->phone }}</a><br><small class="text-muted">{{ $b->email }}</small></td>
                <td>{{ $b->vehicle_plate }}<br><small class="text-muted">{{ $b->vehicle_brand }} {{ $b->vehicle_model }}</small></td>
                <td><small>{{ Str::limit($b->complaint, 80) }}</small></td>
                <td>
                    <form action="{{ route('bookings.update', $b) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 130px;">
                            <option value="pending" {{ $b->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $b->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="in_progress" {{ $b->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="done" {{ $b->status === 'done' ? 'selected' : '' }}>Done</option>
                            <option value="cancelled" {{ $b->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </form>
                </td>
                <td>
                    <form action="{{ route('bookings.destroy', $b) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus booking?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty<tr><td colspan="7" class="text-center text-muted py-3">Belum ada booking.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $bookings->links() }}
</div></div>
@endsection
