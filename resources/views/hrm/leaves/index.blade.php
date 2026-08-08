@extends('layouts.app')
@section('title', 'Cuti / Izin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Cuti & Izin
        @if($pendingCount > 0)
            <span class="badge bg-warning ms-2">{{ $pendingCount }} pending</span>
        @endif
    </h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeaveModal">
        <i class="bi bi-plus-lg"></i> Ajukan Cuti
    </button>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="user_id" class="form-select">
                    <option value="">Semua Karyawan</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- List --}}
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>Karyawan</th>
                <th>Tipe</th>
                <th>Dari</th>
                <th>Sampai</th>
                <th>Alasan</th>
                <th>Status</th>
                <th>Approver</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leaves as $leave)
                <tr>
                    <td><strong>{{ $leave->user->name }}</strong></td>
                    <td>
                        @if($leave->type === 'cuti') <span class="badge bg-info">Cuti</span>
                        @elseif($leave->type === 'sakit') <span class="badge bg-warning text-dark">Sakit</span>
                        @else <span class="badge bg-secondary">Izin</span>
                        @endif
                    </td>
                    <td>{{ $leave->start_date->format('d/m/Y') }}</td>
                    <td>{{ $leave->end_date->format('d/m/Y') }}</td>
                    <td><small>{{ $leave->reason ?: '-' }}</small></td>
                    <td>
                        @if($leave->status === 'approved') <span class="badge bg-success">Disetujui</span>
                        @elseif($leave->status === 'rejected') <span class="badge bg-danger">Ditolak</span>
                        @else <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </td>
                    <td>{{ $leave->approver?->name ?: '-' }}</td>
                    <td>
                        @if($leave->status === 'pending')
                            <div class="d-flex gap-1">
                                <form action="{{ route('hrm.leaves.approve', $leave) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <button class="btn btn-sm btn-danger" title="Tolak" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}"><i class="bi bi-x-lg"></i></button>
                                <form action="{{ route('hrm.leaves.destroy', $leave) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                            {{-- Reject Modal --}}
                            <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('hrm.leaves.reject', $leave) }}" method="POST" class="modal-content">
                                        @csrf
                                        <div class="modal-header"><h5 class="modal-title">Tolak Cuti</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                        <div class="modal-body">
                                            <label class="form-label">Alasan Penolakan</label>
                                            <textarea name="rejection_reason" class="form-control" rows="2"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">Tolak</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            @if($leave->rejection_reason)
                                <small class="text-danger d-block">{{ $leave->rejection_reason }}</small>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data cuti/izin.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $leaves->links() }}

{{-- Add Modal --}}
<div class="modal fade" id="addLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('hrm.leaves.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Ajukan Cuti / Izin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Karyawan *</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Pilih</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ auth()->id() == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipe *</label>
                    <select name="type" class="form-select" required>
                        <option value="cuti">Cuti</option>
                        <option value="sakit">Sakit</option>
                        <option value="izin">Izin</option>
                    </select>
                </div>
                <div class="row mb-3">
                    <div class="col-6"><label class="form-label">Dari *</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="col-6"><label class="form-label">Sampai *</label><input type="date" name="end_date" class="form-control" required></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alasan</label>
                    <textarea name="reason" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Ajukan</button>
            </div>
        </form>
    </div>
</div>
@endsection
