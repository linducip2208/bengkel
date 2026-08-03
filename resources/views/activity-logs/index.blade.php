@extends('layouts.app')
@section('title', 'Activity Log')
@section('content')
<div class="d-flex justify-content-between mb-4"><h4><i class="bi bi-clock-history me-2"></i>Activity Log (Audit User)</h4></div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3"><select name="user_id" class="form-select">
            <option value="">Semua User</option>
            @foreach($users as $u)<option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>@endforeach
        </select></div>
        <div class="col-md-2"><input type="text" name="event" value="{{ request('event') }}" class="form-control" placeholder="Event"></div>
        <div class="col-md-2"><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
    <table class="table table-hover align-middle small">
        <thead class="table-light"><tr><th>Waktu</th><th>User</th><th>Event</th><th>Target</th><th>Deskripsi</th><th>IP</th></tr></thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('d M H:i:s') }}</td>
                <td><strong>{{ $log->user?->name ?? 'system' }}</strong></td>
                <td><span class="badge bg-info">{{ $log->event }}</span></td>
                <td>{{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '-' }}</td>
                <td>{{ Str::limit($log->description, 80) }}</td>
                <td class="text-muted">{{ $log->ip }}</td>
            </tr>
            @empty<tr><td colspan="6" class="text-center text-muted py-3">Belum ada activity log.</td></tr>@endforelse
        </tbody>
    </table>
    {{ $logs->links() }}
</div></div>
@endsection
