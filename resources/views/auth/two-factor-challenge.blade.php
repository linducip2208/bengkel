@extends('layouts.app')
@section('title', 'Verifikasi 2FA')
@section('content')
<div class="row justify-content-center"><div class="col-md-5">
    <div class="card"><div class="card-body p-4">
        <h4 class="text-center"><i class="bi bi-shield-lock me-2 text-primary"></i>Verifikasi 2FA</h4>
        <p class="text-muted text-center small">Kode OTP 6 digit sudah dikirim ke email Anda. Berlaku 10 menit.</p>
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        <form action="{{ route('2fa.verify') }}" method="POST">
            @csrf
            <input type="text" name="code" class="form-control form-control-lg text-center" style="letter-spacing: 0.3em; font-size: 1.5rem;" maxlength="6" pattern="\d{6}" required autofocus placeholder="••••••">
            <button class="btn btn-primary btn-lg w-100 mt-3">Verifikasi</button>
        </form>
    </div></div>
</div></div>
@endsection
