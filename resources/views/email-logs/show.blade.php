@extends('layouts.app')
@section('title', 'Detail Log Notifikasi')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Detail Log #{{ $log->id }}</h4>
    <a href="{{ route('email-logs.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <dl class="row">
        <dt class="col-sm-3">Waktu</dt>
        <dd class="col-sm-9">{{ $log->created_at->format('d M Y H:i:s') }}</dd>
        <dt class="col-sm-3">Penerima</dt>
        <dd class="col-sm-9">{{ $log->to }}</dd>
        <dt class="col-sm-3">Subject</dt>
        <dd class="col-sm-9">{{ $log->subject }}</dd>
        <dt class="col-sm-3">Status</dt>
        <dd class="col-sm-9">
            @if($log->status === 'sent')<span class="badge bg-success">Sent</span>
            @elseif($log->status === 'failed')<span class="badge bg-danger">Failed</span>
            @else<span class="badge bg-secondary">{{ $log->status }}</span>@endif
        </dd>
        @if($log->error_message)
        <dt class="col-sm-3">Error</dt>
        <dd class="col-sm-9"><pre class="text-danger small mb-0">{{ $log->error_message }}</pre></dd>
        @endif
        <dt class="col-sm-3">Body</dt>
        <dd class="col-sm-9"><div class="border rounded p-3 bg-light">{!! nl2br(e($log->body)) !!}</div></dd>
    </dl>
</div></div>
@endsection
