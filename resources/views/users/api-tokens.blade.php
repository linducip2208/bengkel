@extends('layouts.app')
@section('title', 'API Tokens')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-key me-2"></i>API Tokens</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTokenModal"><i class="fas fa-plus me-1"></i>Generate Token</button>
</div>
<div class="card"><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Nama</th><th>Token</th><th>Last Used</th><th>Expires</th><th></th></tr></thead><tbody>
@forelse(auth()->user()->tokens as $token)
<tr>
    <td>{{ $token->name }}</td>
    <td><code>{{ Str::limit($token->token, 20) }}...</code></td>
    <td>{{ $token->last_used_at?->diffForHumans() ?? 'Never' }}</td>
    <td>{{ $token->expires_at?->format('d/m/Y') ?? 'Never' }}</td>
    <td>
        <form method="POST" action="{{ url('/admin/api-tokens/'.$token->id) }}" onsubmit="return confirm('Revoke token ini?')">@csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Revoke</button>
        </form>
    </td>
</tr>
@empty
<tr><td colspan="5" class="text-center py-3 text-muted">Belum ada API token.</td></tr>
@endforelse
</tbody></table></div></div></div>

<div class="modal fade" id="createTokenModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="POST" action="{{ url('/admin/api-tokens') }}">
    @csrf
    <div class="modal-header"><h5>Generate API Token</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label>Nama Token *</label>
        <input type="text" name="name" class="form-control" placeholder="Mobile App, Integration..." required>
        <small class="text-muted mt-2">Token hanya ditampilkan sekali. Simpan baik-baik!</small>
    </div>
    <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-key me-1"></i>Generate</button>
    </div>
</form>
</div></div></div>

@if(session('plain_text_token'))
<div class="alert alert-success mt-3">
    <strong>Token baru:</strong><br>
    <code style="word-break:break-all;">{{ session('plain_text_token') }}</code><br>
    <small class="text-danger">Simpan sekarang! Token tidak akan ditampilkan lagi.</small>
</div>
@endif
@endsection
