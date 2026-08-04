<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Customer Portal — {{ config('app.name', '{{ config('app.name') }}') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>body{background:linear-gradient(135deg,#1e40af,#3b82f6);min-height:100vh;display:flex;align-items:center;padding:2rem;}</style>
</head>
<body>
    <div class="container">
        <div class="card mx-auto" style="max-width: 420px; border-radius: 18px;">
            <div class="card-body p-4">
                <h4 class="text-center mb-1"><i class="bi bi-person-circle text-primary me-2"></i>Customer Portal</h4>
                <p class="text-muted text-center small">{{ config('app.name', '{{ config('app.name') }}') }}</p>

                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                <form method="POST" action="{{ route('customer.login.submit') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nomor HP <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required autofocus placeholder="08xxx atau 62xxx">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required placeholder="Default: 6 digit terakhir HP">
                        <small class="text-muted">Pertama kali? Pakai 6 digit terakhir nomor HP Anda.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Login</button>
                </form>
                <hr>
                <p class="text-center small text-muted mb-0">
                    Belum daftar? <a href="{{ route('public.booking') }}">Booking service</a> dulu — kontak Anda otomatis terdaftar.<br>
                    <a href="https://wa.me/6281296052010">Hubungi support 081296052010</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
