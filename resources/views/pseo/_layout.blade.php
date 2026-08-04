<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $metaTitle ?? '{{ config('app.name') }}' }}</title>
    <meta name="description" content="{{ $metaDescription ?? 'Aplikasi manajemen bengkel modern: service, inventory, POS, invoice, customer, keuangan.' }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ request()->url() }}">
    <meta property="og:title" content="{{ $metaTitle ?? '{{ config('app.name') }}' }}">
    <meta property="og:description" content="{{ $metaDescription ?? '' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle ?? '{{ config('app.name') }}' }}">
    <meta name="twitter:description" content="{{ $metaDescription ?? '' }}">
    @if(isset($jsonLd))
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing:border-box;margin:0;padding:0 }
        body { font-family:'Inter',system-ui,-apple-system,sans-serif;color:#0f172a;line-height:1.7;background:#f8fafc }
        .container { max-width:900px;margin:0 auto;padding:1.5rem }
        .header { background:linear-gradient(135deg,#1e293b,#0f172a);color:#fff;padding:2.5rem 1.5rem;text-align:center }
        .header h1 { font-size:2rem;font-weight:800;margin-bottom:0.5rem;letter-spacing:-0.02em }
        .header p { font-size:1.05rem;color:#94a3b8;max-width:700px;margin:0 auto }
        .content { background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:2rem;margin:1.5rem 0;box-shadow:0 1px 3px rgba(0,0,0,0.06) }
        .content h2 { font-size:1.5rem;font-weight:700;margin:1.5rem 0 0.75rem;color:#1e293b }
        .content h2:first-child { margin-top:0 }
        .content h3 { font-size:1.15rem;font-weight:600;margin:1.25rem 0 0.5rem;color:#334155 }
        .content p { margin-bottom:1rem;color:#475569;font-size:1rem }
        .content ul { padding-left:1.5rem;margin-bottom:1rem }
        .content li { margin-bottom:0.4rem;color:#475569 }
        .sc-cta { background:linear-gradient(135deg,#2563eb,#6366f1);color:#fff;border-radius:12px;padding:1.75rem 2rem;margin:2rem 0;text-align:center }
        .sc-cta h3 { color:#fff;font-size:1.25rem;margin-bottom:0.5rem }
        .sc-cta p { color:rgba(255,255,255,0.92);font-size:0.95rem;margin-bottom:1rem }
        .sc-cta .btn { display:inline-block;background:#fff;color:#2563eb;padding:0.7rem 1.5rem;border-radius:8px;font-weight:700;text-decoration:none }
        .sc-cta .btn:hover { background:#f8fafc }
        .faq-item { margin-bottom:1.25rem;padding-bottom:1rem;border-bottom:1px solid #f1f5f9 }
        .faq-item:last-child { border-bottom:0 }
        footer { text-align:center;padding:1.5rem;color:#94a3b8;font-size:0.85rem }
        footer a { color:#2563eb;text-decoration:none }
        @media(max-width:640px) {
            .header { padding:1.75rem 1rem }
            .header h1 { font-size:1.4rem }
            .content { padding:1.25rem }
            .sc-cta { padding:1.25rem 1rem }
        }
    </style>
    @stack('head')
</head>
<body>
    <div class="header">
        <h1>{{ $metaTitle ?? '{{ config('app.name') }}' }}</h1>
        <p>{{ $metaDescription ?? '' }}</p>
    </div>
    <div class="container">
        @yield('content')
    </div>
    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }} &middot; <a href="{{ url('/') }}">Home</a> &middot; <a href="{{ url('/docs') }}">Docs</a> &middot; <a href="https://wa.me/6281296052010">WhatsApp</a></p>
    </footer>

<x-whatsapp-cta
    message="Halo {{ config('app.name') }}, saya butuh informasi bengkel."
    label="Chat WhatsApp" />
</body>
</html>
