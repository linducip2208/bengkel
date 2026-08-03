<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Berhasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>body{background:linear-gradient(135deg,#10b981,#059669);min-height:100vh;display:flex;align-items:center;padding:2rem;}</style>
</head>
<body>
    <div class="container">
        <div class="card mx-auto" style="max-width: 520px; border-radius: 18px; overflow: hidden;">
            <div class="card-body text-center p-4">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                <h3 class="mt-3">Terima Kasih, {{ $name }}!</h3>
                <p class="text-muted">Booking Anda untuk <strong>{{ \Carbon\Carbon::parse($booking_at)->format('d M Y H:i') }}</strong> sudah kami terima.</p>
                <p class="text-muted small">Tim kami akan menghubungi Anda via WhatsApp untuk konfirmasi dalam 1×24 jam.</p>
                <a href="{{ route('public.booking') }}" class="btn btn-success"><i class="bi bi-arrow-left me-1"></i>Booking Lain</a>
                <a href="https://wa.me/6281296052010" class="btn btn-outline-success"><i class="bi bi-whatsapp me-1"></i>Chat Support</a>
            </div>
        </div>
    </div>
</body>
</html>
