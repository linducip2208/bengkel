<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>410 — {{ config('product.name', config('app.name')) }}</title>
    <style>
        :root { --brand: #2878f0; --ink: #0f172a; --muted: #64748b; --bg: #f8fafc; --line: #e2e8f0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--bg); color: var(--ink); min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .error-card {
            background: #fff; border: 1px solid var(--line); border-radius: 20px;
            box-shadow: 0 24px 60px -24px rgba(15, 23, 42, 0.18);
            padding: 48px 40px; max-width: 560px; width: 100%; text-align: center;
        }
        .error-code {
            font-size: 84px; font-weight: 800; letter-spacing: -0.03em; line-height: 1;
            background: linear-gradient(135deg, var(--brand) 0%, #6366f1 100%);
            -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 16px;
        }
        .error-brand { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--brand); margin-bottom: 12px; }
        .error-title { font-size: 22px; font-weight: 800; margin-bottom: 8px; }
        .error-message { font-size: 15px; color: var(--muted); line-height: 1.6; margin-bottom: 20px; }
        .error-links { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 28px; }
        .error-links a {
            padding: 9px 16px; border: 1px solid var(--line); border-radius: 10px;
            color: var(--ink); text-decoration: none; font-size: 13px; font-weight: 600;
        }
        .error-links a:hover { border-color: var(--brand); color: var(--brand); }
        .error-btn {
            display: inline-block; padding: 13px 28px;
            background: linear-gradient(135deg, var(--brand) 0%, #6366f1 100%);
            color: #fff; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 12px;
            box-shadow: 0 10px 24px -8px rgba(37, 99, 235, 0.5); transition: transform .15s;
        }
        .error-btn:hover { transform: translateY(-1px); }
        .error-foot { margin-top: 24px; font-size: 12px; color: #94a3b8; }
        @media (max-width: 520px) { .error-card { padding: 36px 24px; } .error-code { font-size: 64px; } }
        @media (prefers-reduced-motion: reduce) { * { transition-duration: 0.01ms !important; } }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-brand">{{ config('product.name', config('app.name')) }}</div>
        <div class="error-code">410</div>
        <h1 class="error-title">Halaman ini sudah tidak tersedia.</h1>
        <p class="error-message">Halaman info lama ini sudah dihapus. Kami sekarang fokus ke aplikasi manajemen bengkel (ERP) berbasis web dengan full source code.</p>
        <div class="error-links">
            <a href="{{ url('/aplikasi-bengkel') }}">Aplikasi Bengkel</a>
            <a href="{{ url('/source-code-aplikasi-bengkel') }}">Source Code</a>
            <a href="{{ url('/harga-aplikasi-bengkel') }}">Harga</a>
        </div>
        <a href="{{ url('/') }}" class="error-btn">&larr; Kembali ke Beranda</a>
        <div class="error-foot">&copy; {{ date('Y') }} {{ config('product.name', config('app.name')) }}</div>
    </div>
</body>
</html>
