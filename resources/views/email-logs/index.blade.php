@extends('layouts.app')
@section('title', 'Log Notifikasi')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Log Notifikasi Email & WA</h4>
</div>
<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari email / subject..."></div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
    </form>
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light">
                <tr><th>Waktu</th><th>Penerima</th><th>Subject</th><th>Status</th><th>Error</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $log->to }}</td>
                    <td>{{ Str::limit($log->subject, 60) }}</td>
                    <td>
                        @if($log->status === 'sent')<span class="badge bg-success">Sent</span>
                        @elseif($log->status === 'failed')<span class="badge bg-danger">Failed</span>
                        @else<span class="badge bg-secondary">{{ $log->status }}</span>@endif
                    </td>
                    <td class="text-danger small">{{ Str::limit($log->error_message, 60) }}</td>
                    <td class="text-end">
                        <a href="{{ route('email-logs.show', $log) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                        <form action="{{ route('email-logs.destroy', $log) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus log ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-3 text-muted">Belum ada log.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end">{{ $logs->links() }}</div>
</div></div>
@endsection
