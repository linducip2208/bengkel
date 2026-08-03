@extends('layouts.app')
@section('title', 'Review Customer')
@section('content')
<div class="d-flex justify-content-between mb-4"><h4><i class="bi bi-star me-2"></i>Review & Rating Customer</h4></div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card border-warning"><div class="card-body text-center">
        <h2 class="text-warning mb-0">{{ number_format($summary['avg'], 2) }} <i class="bi bi-star-fill"></i></h2>
        <small class="text-muted">Rata-rata Rating ({{ $summary['count'] }} review published)</small>
    </div></div></div>
    <div class="col-md-4"><div class="card border-info"><div class="card-body text-center">
        <h2 class="text-info">{{ $summary['count'] }}</h2>
        <small class="text-muted">Review Published</small>
    </div></div></div>
    <div class="col-md-4"><div class="card border-danger"><div class="card-body text-center">
        <h2 class="text-danger">{{ $summary['pending'] }}</h2>
        <small class="text-muted">Pending Approval</small>
    </div></div></div>
</div>

<div class="card"><div class="card-body">
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-2"><select name="status" class="form-select">
            <option value="">Semua</option>
            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
        </select></div>
        <div class="col-md-2"><select name="rating" class="form-select">
            <option value="">Semua Rating</option>
            @for($i=5;$i>=1;$i--)<option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} Star</option>@endfor
        </select></div>
        <div class="col-md-2"><button class="btn btn-outline-secondary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>

    @forelse($reviews as $r)
    <div class="border rounded p-3 mb-3 {{ $r->is_published ? '' : 'bg-light' }}">
        <div class="d-flex justify-content-between">
            <div>
                @for($i=1;$i<=5;$i++)<i class="bi bi-star-fill {{ $i <= $r->rating ? 'text-warning' : 'text-light' }}"></i>@endfor
                <strong class="ms-2">{{ $r->reviewer_name }}</strong>
                @if($r->service)<small class="text-muted">— Service {{ $r->service->job_no }}</small>@endif
                <small class="text-muted">• {{ $r->created_at->diffForHumans() }}</small>
                @if($r->is_published)<span class="badge bg-success ms-2">Published</span>@else<span class="badge bg-warning text-dark ms-2">Pending</span>@endif
            </div>
            <div>
                @if(!$r->is_published)
                <form action="{{ route('reviews.publish', $r) }}" method="POST" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-success" title="Publish"><i class="bi bi-check2"></i></button></form>
                @else
                <form action="{{ route('reviews.unpublish', $r) }}" method="POST" class="d-inline">@csrf @method('PUT')<button class="btn btn-sm btn-warning" title="Unpublish"><i class="bi bi-eye-slash"></i></button></form>
                @endif
                <form action="{{ route('reviews.destroy', $r) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>
            </div>
        </div>
        <p class="mt-2 mb-2">{{ $r->comment }}</p>

        @if($r->admin_reply)
        <div class="ps-3 border-start ms-3 small text-muted"><strong>Reply:</strong> {{ $r->admin_reply }}</div>
        @endif

        <form action="{{ route('reviews.reply', $r) }}" method="POST" class="mt-2">
            @csrf @method('PUT')
            <div class="input-group input-group-sm">
                <input type="text" name="admin_reply" class="form-control" placeholder="Balas review..." value="{{ $r->admin_reply }}">
                <button class="btn btn-outline-primary"><i class="bi bi-reply"></i></button>
            </div>
        </form>
    </div>
    @empty
    <div class="text-center text-muted py-4">Belum ada review.</div>
    @endforelse

    {{ $reviews->links() }}
</div></div>
@endsection
