@extends('layouts.app')
@section('title', 'Two-Factor Authentication')
@section('content')
<div class="row justify-content-center"><div class="col-md-7">
<div class="card"><div class="card-body p-4">
    <h4><i class="bi bi-shield-lock me-2 text-primary"></i>Two-Factor Authentication (2FA)</h4>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @if(auth()->user()->two_factor_enabled)
        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>2FA <strong>AKTIF</strong> untuk akun {{ auth()->user()->email }}.</div>
        <p class="text-muted">Setiap login akan minta kode OTP 6 digit yang dikirim ke email Anda. Berlaku 10 menit.</p>
        <form action="{{ route('2fa.disable') }}" method="POST" onsubmit="return confirm('Yakin nonaktifkan 2FA?')">
            @csrf
            <button class="btn btn-danger"><i class="bi bi-shield-slash me-1"></i>Disable 2FA</button>
        </form>
    @else
        <div class="alert alert-warning"><i class="bi bi-shield-exclamation me-2"></i>2FA belum aktif. Akun Anda hanya dilindungi password.</div>
        <p>Dengan 2FA enabled:</p>
        <ul>
            <li>Setiap login butuh kode OTP yang dikirim ke email {{ auth()->user()->email }}</li>
            <li>OTP berlaku 10 menit</li>
            <li>Hacker tidak bisa masuk meskipun password bocor</li>
        </ul>
        <form action="{{ route('2fa.enable') }}" method="POST">
            @csrf
            <button class="btn btn-success btn-lg"><i class="bi bi-shield-fill-check me-1"></i>Aktifkan 2FA Sekarang</button>
        </form>
    @endif
</div></div>
</div></div>
@endsection
