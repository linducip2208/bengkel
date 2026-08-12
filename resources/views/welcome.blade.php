<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Aplikasi Manajemen Bengkel Modern</title>
    <meta name="description" content="ERP Bengkel Modern — 80+ modul terintegrasi: 13-step workflow, multi-cabang, auto-accounting, inventory, POS, CRM, loyalty, dan laporan keuangan dalam satu sistem.">
    <link rel="canonical" href="{{ url('/') }}">
    <meta property="og:title" content="{{ config('app.name') }} — Manajemen Bengkel Modern">
    <meta property="og:description" content="Catat customer, kendaraan, service, parts, invoice, dan keuangan bengkel dalam satu aplikasi.">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --c-ink: #0b1220;
            --c-ink-soft: #1e293b;
            --c-muted: #64748b;
            --c-line: #e2e8f0;
            --c-bg: #f8fafc;
            --c-primary: #2563eb;
            --c-primary-dark: #1d4ed8;
            --c-accent: #f59e0b;
            --c-success: #10b981;
            --c-violet: #8b5cf6;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--c-ink);
            line-height: 1.6;
            background: #fff;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--c-primary); text-decoration: none; }
        a:hover { color: var(--c-primary-dark); }
        h1, h2, h3, h4 { font-weight: 800; letter-spacing: -0.02em; line-height: 1.2; color: var(--c-ink); margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }

        /* === ANIMATIONS === */
        @keyframes scanLine { 0%{top:0} 50%{top:95%} 100%{top:0} }
        @keyframes floatSlow { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-12px)} }
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
        @keyframes fadeSlideUp { 0%{transform:translateY(40px);opacity:0} 100%{transform:translateY(0);opacity:1} }
        @keyframes scaleIn { 0%{transform:scale(.85);opacity:0} 100%{transform:scale(1);opacity:1} }
        @keyframes pingSlow { 0%{transform:scale(1);opacity:1} 100%{transform:scale(1.5);opacity:0} }
        .card-lift { transition: transform .35s,box-shadow .35s; }
        .card-lift:hover { transform:translateY(-6px);box-shadow:0 24px 48px -12px rgba(0,0,0,.18); }
        .reveal { opacity:0;transform:translateY(30px);transition:opacity .7s,transform .7s cubic-bezier(.16,1,.3,1); }
        .reveal.visible { opacity:1;transform:translateY(0); }

        /* Topbar */
        .nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--c-line);
        }
        .nav-inner { display: flex; align-items: center; justify-content: space-between; height: 64px; }
        .brand {
            font-weight: 800; font-size: 1.15rem; color: var(--c-ink);
            display: flex; align-items: center; gap: 0.55rem;
        }
        .brand i { color: var(--c-primary); }
        .nav-links { display: flex; gap: 0.4rem; align-items: center; }
        .nav-link {
            padding: 0.45rem 0.9rem; color: var(--c-ink-soft);
            border-radius: 6px; font-size: 0.92rem; font-weight: 500;
        }
        .nav-link:hover { background: var(--c-bg); color: var(--c-ink); }
        .btn {
            display: inline-flex; align-items: center; gap: 0.45rem;
            padding: 0.6rem 1.1rem; border-radius: 8px;
            font-weight: 600; font-size: 0.92rem;
            border: 1px solid transparent; cursor: pointer; transition: all 0.15s;
        }
        .btn-primary { background: var(--c-primary); color: #fff; }
        .btn-primary:hover { background: var(--c-primary-dark); color: #fff; }
        .btn-ghost { color: var(--c-ink-soft); border-color: var(--c-line); background: #fff; }
        .btn-ghost:hover { border-color: var(--c-ink-soft); }

        /* Hero */
        .hero {
            background:
                radial-gradient(1000px 500px at 90% -10%, #dbeafe 0%, transparent 60%),
                radial-gradient(700px 400px at -10% 30%, #ede9fe 0%, transparent 60%),
                #fff;
            padding: 5rem 0 4rem;
            border-bottom: 1px solid var(--c-line);
        }
        .hero-grid { display: grid; grid-template-columns: 1fr 1.1fr; gap: 3rem; align-items: center; }
        .badge {
            display: inline-flex; align-items: center; gap: 0.35rem;
            padding: 0.32rem 0.7rem; border-radius: 999px;
            background: #eff6ff; color: var(--c-primary);
            font-size: 0.78rem; font-weight: 600;
            border: 1px solid #dbeafe;
            margin-bottom: 1.25rem;
        }
        .hero h1 { font-size: 3rem; margin-bottom: 1.1rem; }
        .hero h1 span { color: var(--c-primary); }
        .hero .lead { font-size: 1.12rem; color: var(--c-muted); margin-bottom: 1.75rem; max-width: 540px; }
        .hero-cta { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .hero-cta .btn { padding: 0.75rem 1.4rem; font-size: 0.98rem; }
        .hero-meta {
            margin-top: 1.5rem; display: flex; gap: 1.5rem; flex-wrap: wrap;
            font-size: 0.88rem; color: var(--c-muted);
        }
        .hero-meta i { color: var(--c-success); margin-right: 0.35rem; }
        .hero-img {
            border-radius: 14px;
            box-shadow:
                0 30px 60px -20px rgba(37,99,235,0.25),
                0 12px 30px -10px rgba(0,0,0,0.15),
                0 0 0 1px rgba(255,255,255,0.6) inset;
            overflow: hidden;
            background: #fff;
            transform: perspective(1200px) rotateY(-6deg) rotateX(2deg);
        }
        .hero-img img { display: block; width: 100%; height: auto; }

        /* Stats */
        .stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-top: 4rem; }
        .stat { text-align: center; padding: 1.25rem 1rem; border-radius: 12px; background: #fff; border: 1px solid var(--c-line); }
        .stat .v { font-size: 1.85rem; font-weight: 800; color: var(--c-ink); }
        .stat .l { font-size: 0.82rem; color: var(--c-muted); margin-top: 0.2rem; font-weight: 500; }

        /* Sections */
        section { padding: 5rem 0; }
        section.alt { background: var(--c-bg); }
        .sec-head { text-align: center; max-width: 720px; margin: 0 auto 3rem; }
        .sec-head .eyebrow {
            font-size: 0.78rem; letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--c-primary); font-weight: 700; margin-bottom: 0.6rem;
        }
        .sec-head h2 { font-size: 2.2rem; margin-bottom: 0.8rem; }
        .sec-head p { color: var(--c-muted); font-size: 1.05rem; margin: 0; }

        /* Feature alternating rows */
        .feature {
            display: grid; grid-template-columns: 1fr 1.15fr; gap: 3.5rem;
            align-items: center; margin-bottom: 5rem;
        }
        .feature.reverse { grid-template-columns: 1.15fr 1fr; }
        .feature.reverse .feature-text { order: 2; }
        .feature.reverse .feature-shot { order: 1; }
        .feature-text .icon-pill {
            width: 44px; height: 44px; border-radius: 10px;
            background: #eff6ff; color: var(--c-primary);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 1rem;
        }
        .feature-text h3 { font-size: 1.7rem; margin-bottom: 0.75rem; }
        .feature-text p { color: var(--c-muted); font-size: 1.02rem; margin: 0 0 1rem; }
        .feature-bullets { list-style: none; padding: 0; margin: 0; }
        .feature-bullets li {
            padding-left: 1.6rem; position: relative;
            color: var(--c-ink-soft); font-size: 0.96rem; margin-bottom: 0.4rem;
        }
        .feature-bullets li::before {
            content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
            position: absolute; left: 0; top: 0.05rem; color: var(--c-success); font-size: 0.85rem;
        }
        .feature-shot { position: relative; }
        .feature-shot img {
            display: block; width: 100%; height: auto;
            border-radius: 12px;
            border: 1px solid var(--c-line);
            box-shadow: 0 25px 50px -15px rgba(15, 23, 42, 0.25), 0 8px 16px -8px rgba(15, 23, 42, 0.1);
        }
        .feature-shot .browser-bar {
            display: flex; align-items: center; gap: 0.35rem;
            padding: 0.55rem 0.85rem;
            background: #f1f5f9; border: 1px solid var(--c-line); border-bottom: 0;
            border-radius: 12px 12px 0 0;
        }
        .feature-shot .browser-bar .dot { width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; }
        .feature-shot .browser-bar .dot.r { background: #fca5a5; }
        .feature-shot .browser-bar .dot.y { background: #fcd34d; }
        .feature-shot .browser-bar .dot.g { background: #86efac; }
        .feature-shot .browser-bar .url {
            margin-left: 0.6rem; font-size: 0.78rem; color: var(--c-muted);
            background: #fff; padding: 0.18rem 0.6rem; border-radius: 4px;
            border: 1px solid var(--c-line);
        }
        .feature-shot.framed img { border-top-left-radius: 0; border-top-right-radius: 0; }

        /* Group label (for clustered features) */
        .group-label {
            text-align: center; margin: 3rem 0 1.5rem;
            font-size: 0.72rem; letter-spacing: 0.16em;
            color: var(--c-muted); text-transform: uppercase; font-weight: 700;
        }
        .group-label::before, .group-label::after {
            content: ''; display: inline-block; width: 60px; height: 1px;
            background: var(--c-line); vertical-align: middle; margin: 0 0.8rem;
        }

        /* Mini grid (smaller features) */
        .mini-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem;
            margin-top: 2rem;
        }
        .mini-card {
            background: #fff; border: 1px solid var(--c-line); border-radius: 12px;
            overflow: hidden; transition: all 0.2s;
        }
        .mini-card:hover {
            box-shadow: 0 12px 30px -10px rgba(37,99,235,0.18);
            transform: translateY(-2px);
            border-color: #c7d2fe;
        }
        .mini-card img {
            display: block; width: 100%; height: 180px; object-fit: cover; object-position: top left;
            border-bottom: 1px solid var(--c-line);
        }
        .mini-card .body { padding: 1.1rem 1.15rem 1.2rem; }
        .mini-card h4 {
            font-size: 1rem; font-weight: 700; margin: 0 0 0.3rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .mini-card h4 i { color: var(--c-primary); font-size: 0.95rem; }
        .mini-card p { font-size: 0.88rem; color: var(--c-muted); margin: 0; }

        /* Module catalog (compact list for the long tail) */
        .modules {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.8rem;
            margin-top: 2rem;
        }
        .module-tile {
            padding: 0.95rem 1rem;
            border: 1px solid var(--c-line); border-radius: 10px;
            background: #fff; display: flex; gap: 0.75rem; align-items: flex-start;
            transition: all 0.15s;
        }
        .module-tile:hover { border-color: #c7d2fe; background: #f8fafc; }
        .module-tile .ic {
            width: 32px; height: 32px; border-radius: 8px;
            background: #eef2ff; color: var(--c-primary);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; flex-shrink: 0;
        }
        .module-tile h5 { font-size: 0.93rem; font-weight: 700; margin: 0 0 0.15rem; color: var(--c-ink); }
        .module-tile p { font-size: 0.8rem; color: var(--c-muted); margin: 0; line-height: 1.4; }

        /* Workflow timeline */
        .timeline { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; }
        .timeline-step {
            background: #fff; border: 1px solid var(--c-line); border-radius: 12px;
            padding: 1.5rem 1.25rem; position: relative;
        }
        .timeline-step .num {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--c-primary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; margin-bottom: 0.85rem;
        }
        .timeline-step h4 { font-size: 1.02rem; margin: 0 0 0.4rem; }
        .timeline-step p { color: var(--c-muted); font-size: 0.88rem; margin: 0; }

        /* CTA */
        .cta {
            background: linear-gradient(135deg, var(--c-primary) 0%, #6366f1 100%);
            color: #fff; text-align: center; padding: 4.5rem 0;
        }
        .cta h2 { color: #fff; font-size: 2.1rem; margin-bottom: 0.6rem; }
        .cta p { color: rgba(255,255,255,0.92); font-size: 1.05rem; margin: 0 0 1.75rem; }
        .cta .btn-primary { background: #fff; color: var(--c-primary); }
        .cta .btn-primary:hover { background: #f8fafc; color: var(--c-primary-dark); }
        .cta .btn-ghost { background: transparent; color: #fff; border-color: rgba(255,255,255,0.4); }
        .cta .btn-ghost:hover { border-color: #fff; background: rgba(255,255,255,0.1); color: #fff; }

        /* Footer */
        footer { background: var(--c-ink); color: #cbd5e1; padding: 3rem 0 1.5rem; }
        footer h5 { color: #fff; font-size: 0.95rem; margin: 0 0 0.85rem; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem; }
        footer ul { list-style: none; padding: 0; margin: 0; }
        footer li { margin-bottom: 0.45rem; }
        footer a { color: #cbd5e1; font-size: 0.9rem; }
        footer a:hover { color: #fff; }
        .footer-bottom {
            border-top: 1px solid #1e293b; padding-top: 1.25rem;
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.85rem; color: #94a3b8;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .hero h1 { font-size: 2.4rem; }
            .hero-grid { gap: 2rem; }
            .sec-head h2 { font-size: 1.8rem; }
            .modules { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 900px) {
            .hero { padding: 3.5rem 0 3rem; }
            .hero-grid { grid-template-columns: 1fr; gap: 2.5rem; }
            .hero h1 { font-size: 2rem; }
            .hero-img { transform: none; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            section { padding: 3.5rem 0; }
            .feature, .feature.reverse { grid-template-columns: 1fr; gap: 1.75rem; }
            .feature.reverse .feature-text { order: 0; }
            .feature.reverse .feature-shot { order: 0; }
            .mini-grid { grid-template-columns: 1fr; }
            .modules { grid-template-columns: 1fr 1fr; }
            .timeline { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .nav-links .nav-link { display: none; }
            .hero .lead { font-size: 1rem; }
        }

        @media (max-width: 640px) {
            .container { padding: 0 1rem; }
            .nav-inner { height: 56px; }
            .brand { font-size: 1rem; }
            .hero { padding: 2.5rem 0 2rem; }
            .hero h1 { font-size: 1.6rem; }
            .hero .lead { font-size: 0.95rem; }
            .hero-cta { flex-direction: column; width: 100%; }
            .hero-cta .btn { justify-content: center; width: 100%; }
            .hero-meta { flex-direction: column; gap: 0.5rem; }
            .hero-img { transform: none; border-radius: 8px; }
            .stats { grid-template-columns: 1fr 1fr; gap: 0.6rem; margin-top: 2rem; }
            .stat { padding: 0.9rem 0.6rem; }
            .stat .v { font-size: 1.4rem; }
            .stat .l { font-size: 0.72rem; }
            section { padding: 2.5rem 0; }
            .sec-head { margin-bottom: 2rem; }
            .sec-head h2 { font-size: 1.5rem; }
            .sec-head p { font-size: 0.92rem; }
            .feature { margin-bottom: 3rem; }
            .feature-text h3 { font-size: 1.35rem; }
            .feature-text p { font-size: 0.92rem; }
            .feature-bullets li { font-size: 0.88rem; }
            .feature-shot img { border-radius: 8px; }
            .feature-shot .browser-bar { border-radius: 8px 8px 0 0; padding: 0.45rem 0.6rem; }
            .feature-shot .browser-bar .url { font-size: 0.7rem; }
            .timeline { gap: 1rem; }
            .timeline-step { padding: 1.15rem 1rem; }
            .timeline-step h4 { font-size: 0.92rem; }
            .cta { padding: 3rem 0; }
            .cta h2 { font-size: 1.5rem; }
            .cta p { font-size: 0.92rem; }
            .modules { grid-template-columns: 1fr; }
            .mini-card img { height: 140px; }
            .footer-grid { grid-template-columns: 1fr; gap: 1.5rem; }
            .footer-bottom { flex-direction: column; gap: 0.5rem; text-align: center; }
            /* Mobile hamburger nav */
            .nav-links { gap: 0.25rem; }
            .nav-links .btn { font-size: 0.82rem; padding: 0.45rem 0.75rem; }
        }

        @media (max-width: 400px) {
            .hero h1 { font-size: 1.4rem; }
            .stats { grid-template-columns: 1fr; }
            .module-tile { padding: 0.75rem 0.85rem; }
            .module-tile h5 { font-size: 0.85rem; }
            .module-tile p { font-size: 0.75rem; }
        }

        /* Touch targets (WCAG 2.5.5) */
        @media (pointer: coarse) {
            .btn { min-height: 44px; }
            .nav-link { min-height: 40px; display: flex; align-items: center; }
            .module-tile { min-height: 60px; }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body>

<nav class="nav">
    <div class="container nav-inner">
        <a href="/" class="brand">
            @if(!empty($appSettings['logo']))
                <img src="{{ asset('storage/' . $appSettings['logo']) }}" alt="Logo" style="height:28px;width:auto;margin-right:6px;vertical-align:middle;">
            @else
                <i class="fas fa-wrench"></i>
            @endif
            {{ $appSettings['name'] ?? config('app.name') }}
        </a>
        <div class="nav-links">
            <a href="#fitur" class="nav-link">Fitur Utama</a>
            <a href="#operasional" class="nav-link">Teknisi & CRM</a>
            <a href="#marketing" class="nav-link">Finance</a>
            <a href="#modul" class="nav-link">85+ Modul</a>
            <a href="{{ route('docs.index') }}" class="nav-link">Dokumentasi</a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                <a href="{{ route('login') }}" class="btn btn-primary"><i class="fas fa-arrow-right-to-bracket"></i> Coba Sekarang</a>
            @endauth
        </div>
    </div>
</nav>

<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div>
                <span class="badge"><i class="fas fa-bolt"></i> ERP Bengkel · 80+ modul · 13-step workflow</span>
                <h1>ERP Bengkel Modern, <span>80+ Modul Terintegrasi</span>.</h1>
                <p class="lead">
                    Multi-cabang, 13-step workflow, auto-accounting, inventory, POS, CRM —
                    satu sistem lengkap untuk mengelola seluruh operasional bengkel dari front-desk
                    sampai laporan keuangan.
                </p>
                <div class="hero-cta">
                    <a href="{{ url('/admin/login') }}" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Coba Demo
                    </a>
                    <a href="{{ route('docs.index') }}" class="btn btn-ghost">
                        <i class="fas fa-book"></i> Lihat Dokumentasi
                    </a>
                </div>
                <div class="hero-meta">
                    <span><i class="fas fa-check-circle"></i> 13-step workflow bengkel</span>
                    <span><i class="fas fa-check-circle"></i> Auto-accounting & journal</span>
                    <span><i class="fas fa-check-circle"></i> Multi-cabang siap pakai</span>
                </div>
            </div>
            <div class="hero-img">
                <img src="{{ asset('images/features/dashboard.png') }}" alt="Dashboard {{ config('app.name') }}">
            </div>
        </div>

        <div class="stats">
            <div class="stat"><div class="v">85+</div><div class="l">Modul Terintegrasi</div></div>
            <div class="stat"><div class="v">15</div><div class="l">Menu Groups</div></div>
            <div class="stat"><div class="v">13</div><div class="l">Workflow Statuses</div></div>
            <div class="stat"><div class="v">10</div><div class="l">Tipe Laporan</div></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-top:2.5rem;">
            <div class="feature-shot framed" style="transform:none;box-shadow:0 20px 40px -15px rgba(37,99,235,0.2),0 6px 12px -6px rgba(0,0,0,0.1);border-radius:12px;overflow:hidden;background:#fff;border:1px solid var(--c-line);">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">app / dashboard</span>
                </div>
                <img src="{{ asset('marketing/screens/dashboard.png') }}" alt="Dashboard Overview" loading="lazy" style="border-top-left-radius:0;border-top-right-radius:0;">
            </div>
            <div class="feature-shot framed" style="transform:none;box-shadow:0 20px 40px -15px rgba(37,99,235,0.2),0 6px 12px -6px rgba(0,0,0,0.1);border-radius:12px;overflow:hidden;background:#fff;border:1px solid var(--c-line);">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">app / services</span>
                </div>
                <img src="{{ asset('marketing/screens/service-list.png') }}" alt="Service List" loading="lazy" style="border-top-left-radius:0;border-top-right-radius:0;">
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION FITUR UTAMA ====================== --}}
<section id="fitur">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Fitur Utama</div>
            <h2>8 modul kunci yang menjalankan operasional bengkel</h2>
            <p>Dari customer datang, booking, service, inventory, POS, sampai laporan keuangan — semua dalam satu sistem terintegrasi.</p>
        </div>

        {{-- Operations --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-building"></i></div>
                <h3>Operations — Multi-Cabang & Booking</h3>
                <p>Kelola cabang, booking online customer, check-in kendaraan, dan gate pass dalam satu alur. Setiap cabang punya jam operasional, slot service, dan hari libur sendiri.</p>
                <ul class="feature-bullets">
                    <li>Multi-cabang dengan branch switcher cepat di topbar</li>
                    <li>Booking online — customer pesan jadwal dari HP</li>
                    <li>Check-in & check-out kendaraan dengan odometer</li>
                    <li>Gate pass PDF dengan stempel waktu masuk/keluar</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/branches</span>
                </div>
                <img src="{{ asset('marketing/screens/branch-list.png') }}" alt="Branch Management" loading="lazy">
            </div>
        </div>

        {{-- Service Management --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-tools"></i></div>
                <h3>Service Management — 13-Step Workflow</h3>
                <p>Jantung aplikasi. 13 status workflow dari booking sampai selesai: job card, inspeksi 6 kategori, assign teknisi, tracking progres, subcontractor, dan serah terima.</p>
                <ul class="feature-bullets">
                    <li>Booking → Check-in → Diagnosis → Estimate → Approval</li>
                    <li>In Progress → Inspection → Subcontract → QC → Done</li>
                    <li>Job card print dengan checklist inspeksi 30+ titik</li>
                    <li>Foto before/after, catatan teknisi, rekomendasi lanjutan</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/services</span>
                </div>
                <img src="{{ asset('marketing/screens/service-create.png') }}" alt="Service Management" loading="lazy">
            </div>
        </div>

        {{-- Inventory & Equipment --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-boxes-stacked"></i></div>
                <h3>Inventory — Multi-Warehouse & Equipment</h3>
                <p>Kelola sparepart, oli, ban, dan equipment di banyak gudang. Stok otomatis berkurang saat dipakai service, otomatis bertambah saat PO diterima. Stock opname & adjustment dengan audit trail lengkap.</p>
                <ul class="feature-bullets">
                    <li>Multi-warehouse dengan transfer stok antar gudang</li>
                    <li>Purchase order → receive → stok auto-bertambah</li>
                    <li>Stock opname & adjustment dengan history trail</li>
                    <li>Equipment tracking: tools, scanner, lift, dan aset bengkel</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/products</span>
                </div>
                <img src="{{ asset('marketing/screens/product-list.png') }}" alt="Inventory & Gudang" loading="lazy">
            </div>
        </div>

        {{-- Sales & POS --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-cash-register"></i></div>
                <h3>Sales & POS — Terminal Kasir + Invoice</h3>
                <p>POS terminal untuk transaksi cepat walk-in customer. Invoice service, sales, dan parts dengan dukungan split payment, cicilan, voucher diskon, dan multi-metode bayar. Generate PDF + kirim via WA/Email.</p>
                <ul class="feature-bullets">
                    <li>POS real-time: scan barcode, klik produk, bayar, cetak struk</li>
                    <li>3 tipe invoice: service, sales, parts — semua terintegrasi</li>
                    <li>Split payment & cicilan dengan status auto-update</li>
                    <li>Voucher & promo dengan masa berlaku dan max usage</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/invoices</span>
                </div>
                <img src="{{ asset('marketing/screens/invoice-list.png') }}" alt="Sales POS & Invoice" loading="lazy">
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION TEKNISI & CRM ====================== --}}
<section id="operasional" class="alt">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Teknisi & CRM</div>
            <h2>Teknisi, marketing, dan retensi pelanggan</h2>
            <p>Komisi teknisi otomatis, loyalty program, voucher promo, dan review customer — semua untuk menahan pelanggan datang kembali.</p>
        </div>

        {{-- Technicians --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill" style="background:#fef3c7;color:#d97706"><i class="fas fa-user-gear"></i></div>
                <h3>Manajemen Teknisi & Komisi</h3>
                <p>Assign teknisi ke service, tracking progres, hitung komisi otomatis berdasarkan service yang dikerjakan. Attendance & leave management untuk tim bengkel.</p>
                <ul class="feature-bullets">
                    <li>Komisi otomatis per service & parts yang dikerjakan</li>
                    <li>Attendance check-in/check-out harian</li>
                    <li>Leave management: izin, sakit, cuti tahunan</li>
                    <li>Leaderboard performa teknisi per periode</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/commissions</span>
                </div>
                <img src="{{ asset('images/features/commissions.png') }}" alt="Komisi Teknisi">
            </div>
        </div>

        {{-- CRM & Loyalty --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill" style="background:#dcfce7;color:#16a34a"><i class="fas fa-medal"></i></div>
                <h3>CRM & Loyalty Program</h3>
                <p>Customer dapat poin dari setiap transaksi. Tier otomatis Bronze → Silver → Gold → Platinum. Voucher promo, review customer, blog content, dan campaign marketing — semua built-in.</p>
                <ul class="feature-bullets">
                    <li>Loyalty points per Rupiah dengan 4 tier membership</li>
                    <li>Voucher diskon nominal & persentase dengan masa berlaku</li>
                    <li>Review & rating customer — material SEO publik</li>
                    <li>Blog, campaign, dan reminder WA/Email otomatis</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/loyalty</span>
                </div>
                <img src="{{ asset('images/features/loyalty.png') }}" alt="Loyalty Program">
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION FINANCE & REPORTS ====================== --}}
<section id="marketing">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Finance & Reports</div>
            <h2>Auto-accounting, 10 tipe laporan, PDF & Excel export</h2>
            <p>Jurnal otomatis dari setiap transaksi, COA, General Ledger, Profit & Loss, Balance Sheet — plus 10 tipe laporan bisnis dengan chart dan export.</p>
        </div>

        {{-- Finance & Accounting --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill" style="background:#ecfdf5;color:#059669"><i class="fas fa-calculator"></i></div>
                <h3>Finance & Accounting — Auto-Journal</h3>
                <p>Chart of Accounts (COA), General Ledger, dan jurnal otomatis dari setiap transaksi: service, sales, purchase, payment, expense. Profit & Loss dan Balance Sheet real-time.</p>
                <ul class="feature-bullets">
                    <li>COA dengan akun kas, piutang, hutang, pendapatan, beban</li>
                    <li>Auto-journal dari setiap invoice, payment, purchase, expense</li>
                    <li>General Ledger, Trial Balance, P&L, Balance Sheet</li>
                    <li>Income & Expense tracking dengan kategori dan kuitansi</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/reports</span>
                </div>
                <img src="{{ asset('images/features/reports.png') }}" alt="Finance & Accounting">
            </div>
        </div>

        {{-- Reports --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-chart-pie"></i></div>
                <h3>10 Tipe Laporan dengan Chart & Export</h3>
                <p>Laporan service, penjualan, stok, keuangan, teknisi, customer, dan lainnya. Setiap laporan dilengkapi chart interaktif (bar, line, doughnut) dan bisa di-export ke PDF & Excel.</p>
                <ul class="feature-bullets">
                    <li>10 tipe laporan: sales, service, stock, finance, technician, dll</li>
                    <li>Date filter + group by harian/mingguan/bulanan</li>
                    <li>Chart interaktif dengan Chart.js</li>
                    <li>Export PDF (DomPDF) & Excel dengan satu klik</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/reports/sales</span>
                </div>
                <img src="{{ asset('marketing/screens/report-service.png') }}" alt="10 Tipe Laporan" loading="lazy">
            </div>
        </div>

        <div class="mini-grid">
            <div class="mini-card">
                <img src="{{ asset('images/features/reports-stock.png') }}" alt="Stock Report">
                <div class="body">
                    <h4><i class="fas fa-warehouse"></i> Laporan Stok</h4>
                    <p>Stok berjalan, slow-moving items, rekomendasi reorder — export PDF/Excel.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/reports-service.png') }}" alt="Service Report">
                <div class="body">
                    <h4><i class="fas fa-wrench"></i> Laporan Service</h4>
                    <p>Volume service per kategori & teknisi — evaluasi performa tim.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/incomes.png') }}" alt="Income & Expense">
                <div class="body">
                    <h4><i class="fas fa-file-invoice-dollar"></i> Income & Expense</h4>
                    <p>Pemasukan non-service + pengeluaran operasional — profit akurat.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/suppliers.png') }}" alt="Supplier">
                <div class="body">
                    <h4><i class="fas fa-truck"></i> Supplier & Purchase</h4>
                    <p>Supplier sparepart, PO, receiving — stok auto-bertambah.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION TATA KELOLA ====================== --}}
<section id="hrm" class="alt">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Sistem & Tata Kelola</div>
            <h2>User, hak akses, audit, dan integrasi</h2>
            <p>Kelola tim, kontrol akses granular, payment gateway dinamis, dan activity log — bengkel siap di-audit kapan saja.</p>
        </div>

        <div class="mini-grid">
            <div class="mini-card">
                <img src="{{ asset('images/features/users.png') }}" alt="User Management">
                <div class="body">
                    <h4><i class="fas fa-user-shield"></i> User Management</h4>
                    <p>Daftarkan karyawan: admin, teknisi, kasir, sales. Aktif/nonaktif, ganti password, assign cabang.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/roles.png') }}" alt="Roles & Access">
                <div class="body">
                    <h4><i class="fas fa-key"></i> Roles & Permissions</h4>
                    <p>Role-based access: setiap role bisa diberi izin spesifik untuk modul-modul tertentu.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/commissions.png') }}" alt="Commissions">
                <div class="body">
                    <h4><i class="fas fa-hand-holding-dollar"></i> Komisi Teknisi & Sales</h4>
                    <p>Hitung komisi otomatis berdasarkan service yang dikerjakan atau parts yang dijual.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/petty-cash.png') }}" alt="Petty Cash">
                <div class="body">
                    <h4><i class="fas fa-wallet"></i> Kas Kecil (Petty Cash)</h4>
                    <p>Catat uang keluar harian kecil (parkir, fotocopy, makan) tanpa harus lewat expense formal.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/payment-gateways.png') }}" alt="Payment Gateways">
                <div class="body">
                    <h4><i class="fas fa-credit-card"></i> Payment Gateway</h4>
                    <p>Setup gateway pembayaran dinamis: Midtrans, Xendit, atau yang lain. User input sendiri di admin UI.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/activity-logs.png') }}" alt="Activity Logs">
                <div class="body">
                    <h4><i class="fas fa-shield"></i> Activity Log Lengkap</h4>
                    <p>Jejak setiap aksi user: login, edit, hapus. Audit-ready untuk keperluan compliance.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION DAFTAR MODUL LENGKAP ====================== --}}
<section id="modul" class="alt">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Katalog Modul Lengkap</div>
            <h2>85+ modul siap pakai</h2>
            <p>Semua kebutuhan bengkel modern dalam satu instalasi — tidak ada add-on terpisah yang harus dibeli.</p>
        </div>

        <div class="modules">
            <div class="module-tile"><div class="ic"><i class="fas fa-gauge-high"></i></div><div><h5>Dashboard</h5><p>Ringkasan operasional real-time.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-users"></i></div><div><h5>Customer</h5><p>CRUD + import CSV + histori.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-car"></i></div><div><h5>Vehicle</h5><p>Plat unik + foto + odometer.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-tools"></i></div><div><h5>Service</h5><p>13-step workflow booking → done.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-clipboard-list"></i></div><div><h5>Jobcard</h5><p>Lembar kerja teknisi + print.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-search"></i></div><div><h5>Observation Points</h5><p>Titik inspeksi 6 kategori.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-list-check"></i></div><div><h5>Inspection Library</h5><p>30+ titik standar yang bisa di-pakai-ulang.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-clipboard-check"></i></div><div><h5>Checkout Categories</h5><p>Item yang dicek sebelum kendaraan keluar.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-ticket-alt"></i></div><div><h5>Gate Pass</h5><p>Surat jalan + stempel waktu.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-file-invoice-dollar"></i></div><div><h5>Invoice</h5><p>3 tipe, PDF, WA, Email.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-money-check-dollar"></i></div><div><h5>Payment</h5><p>Multi-method, partial allowed.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-boxes-stacked"></i></div><div><h5>Inventory</h5><p>Produk + stok + harga.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-cart-shopping"></i></div><div><h5>Purchase</h5><p>PO ke supplier + received.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-truck"></i></div><div><h5>Supplier</h5><p>CRUD + history pembelian.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-cash-register"></i></div><div><h5>Sales / POS</h5><p>Jual parts walk-in tanpa service.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-arrow-trend-up"></i></div><div><h5>Income</h5><p>Pemasukan non-operasional.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-arrow-trend-down"></i></div><div><h5>Expense</h5><p>Pengeluaran + kuitansi.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-building"></i></div><div><h5>Cabang</h5><p>Multi-branch + switcher.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-clock"></i></div><div><h5>Business Hours</h5><p>Jam operasional per hari.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-calendar-xmark"></i></div><div><h5>Hari Libur</h5><p>Libur sekali atau berulang.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-warehouse"></i></div><div><h5>Washbay</h5><p>Slot service real-time.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-chart-line"></i></div><div><h5>Reports</h5><p>10 jenis + ekspor PDF/Excel.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-bell"></i></div><div><h5>Reminder</h5><p>Reminder service & ulang tahun.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-envelope"></i></div><div><h5>Notif Templates</h5><p>WA + Email dengan variabel.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-clock-rotate-left"></i></div><div><h5>Stock History</h5><p>Audit trail per produk.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-envelope-open-text"></i></div><div><h5>Email Log</h5><p>Verifikasi keterkiriman.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-note-sticky"></i></div><div><h5>Notes</h5><p>Catatan polymorphic.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-puzzle-piece"></i></div><div><h5>Custom Fields</h5><p>Field tambahan tanpa coding.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-globe"></i></div><div><h5>Geografi</h5><p>Negara, provinsi, kota.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-coins"></i></div><div><h5>Currency</h5><p>Multi-currency + default IDR.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-database"></i></div><div><h5>Master Data</h5><p>13 jenis siap pakai.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-cog"></i></div><div><h5>Settings</h5><p>Profil bengkel, branding, dll.</p></div></div>
            {{-- Marketing & Loyalty --}}
            <div class="module-tile"><div class="ic"><i class="fas fa-cash-register"></i></div><div><h5>POS Kasir</h5><p>Terminal kasir real-time.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-calendar-check"></i></div><div><h5>Booking Online</h5><p>Customer pesan dari HP.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-tags"></i></div><div><h5>Voucher / Promo</h5><p>Kode promo dengan masa berlaku.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-medal"></i></div><div><h5>Loyalty Points</h5><p>4 tier + leaderboard.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-star"></i></div><div><h5>Review Customer</h5><p>Rating + feedback publik.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-shield-halved"></i></div><div><h5>Klaim Garansi</h5><p>Tracking sampai selesai.</p></div></div>
            {{-- HRM --}}
            <div class="module-tile"><div class="ic"><i class="fas fa-user-shield"></i></div><div><h5>User Management</h5><p>Karyawan + assign cabang.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-key"></i></div><div><h5>Roles & Access</h5><p>RBAC granular per modul.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-hand-holding-dollar"></i></div><div><h5>Komisi</h5><p>Auto-hitung teknisi & sales.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-wallet"></i></div><div><h5>Petty Cash</h5><p>Kas kecil harian.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-credit-card"></i></div><div><h5>Payment Gateway</h5><p>Midtrans, Xendit, dll.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-shield"></i></div><div><h5>Activity Log</h5><p>Jejak aksi user.</p></div></div>
        </div>
    </div>
</section>

{{-- ====================== SECTION ALUR KERJA ====================== --}}
<section>
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Alur Kerja</div>
            <h2>13-step workflow — dari booking sampai serah terima</h2>
            <p>Alur lengkap yang mencerminkan operasional bengkel nyata — setiap langkah punya status transparan dan terdokumentasi.</p>
        </div>

        <div class="timeline">
            <div class="timeline-step">
                <div class="num">1-4</div>
                <h4>Booking → Check-in</h4>
                <p>Customer booking online atau walk-in. Operator check-in kendaraan: catat plat, odometer, keluhan, dan foto kondisi masuk.</p>
            </div>
            <div class="timeline-step">
                <div class="num">5-8</div>
                <h4>Diagnosis → In Progress</h4>
                <p>Teknisi diagnosis, buat estimasi biaya, minta approval customer. Setelah approve, mulai pengerjaan dengan jobcard & inspection checklist.</p>
            </div>
            <div class="timeline-step">
                <div class="num">9-11</div>
                <h4>QC → Done</h4>
                <p>Quality control setelah pengerjaan selesai. Subcontract ke pihak ketiga jika diperlukan. Foto after sebagai bukti.</p>
            </div>
            <div class="timeline-step">
                <div class="num">12-13</div>
                <h4>Invoice → Serah Terima</h4>
                <p>Generate invoice, terima pembayaran (full/cicil), cetak gate pass. Auto-set reminder service berikutnya via WA/Email.</p>
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION DEMO & PRICING ====================== --}}
<section id="demo" class="alt">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Akun Demo</div>
            <h2>Coba semua fitur dengan akun demo</h2>
            <p>Gunakan kredensial di bawah untuk login dan eksplorasi semua 85+ modul {{ config('app.name') }}.</p>
        </div>

        <div class="table-responsive" style="max-width:700px;margin:0 auto;">
            <table class="table table-bordered" style="background:#fff;border-radius:12px;overflow:hidden;">
                <thead style="background:var(--c-primary);color:#fff;">
                    <tr>
                        <th style="font-size:0.85rem;">Role</th>
                        <th style="font-size:0.85rem;">Email</th>
                        <th style="font-size:0.85rem;">Password</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge bg-primary">Admin</span></td>
                        <td><code style="font-size:0.82rem;">admin@bengkel.test</code></td>
                        <td><code style="font-size:0.82rem;">password</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-info">Manager</span></td>
                        <td><code style="font-size:0.82rem;">manager@bengkel.test</code></td>
                        <td><code style="font-size:0.82rem;">password</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-success">Kasir</span></td>
                        <td><code style="font-size:0.82rem;">kasir@bengkel.test</code></td>
                        <td><code style="font-size:0.82rem;">password</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-warning text-dark">Teknisi</span></td>
                        <td><code style="font-size:0.82rem;">teknisi@bengkel.test</code></td>
                        <td><code style="font-size:0.82rem;">password</code></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-secondary">Sales</span></td>
                        <td><code style="font-size:0.82rem;">sales@bengkel.test</code></td>
                        <td><code style="font-size:0.82rem;">password</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- ====================== SECTION PRICING ====================== --}}
<section id="pricing">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Harga</div>
            <h2>Paket yang sesuai dengan skala bengkel Anda</h2>
            <p>Dari bengkel kecil satu cabang sampai jaringan multi-cabang — semua dilayani.</p>
        </div>

        <div class="row g-4 justify-content-center" style="max-width:1000px;margin:0 auto;">
            <div class="col-md-4">
                <div class="card h-100 border" style="border-radius:14px;">
                    <div class="card-body text-center p-4">
                        <span class="badge bg-light text-dark mb-3" style="font-size:0.8rem;padding:0.4rem 0.8rem;">Starter</span>
                        <h3 style="font-size:2rem;font-weight:800;">Gratis</h3>
                        <p class="text-muted" style="font-size:0.88rem;">Untuk bengkel kecil 1 cabang</p>
                        <ul class="list-unstyled text-start mt-3" style="font-size:0.88rem;">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>3 user</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>1 cabang</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Modul dasar</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Laporan standar</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>POS & Booking</li>
                            <li class="mb-2"><i class="fas fa-times text-danger me-2"></i>Multi-cabang</li>
                        </ul>
                        <a href="{{ route('login') }}" class="btn btn-outline-primary w-100 mt-3" style="border-radius:10px;">Mulai Gratis</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm" style="border-radius:14px;border:2px solid var(--c-primary);position:relative;">
                    <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--c-primary);color:#fff;padding:0.2rem 1rem;border-radius:20px;font-size:0.75rem;font-weight:700;">POPULER</div>
                    <div class="card-body text-center p-4">
                        <span class="badge bg-primary mb-3" style="font-size:0.8rem;padding:0.4rem 0.8rem;">Growth</span>
                        <h3 style="font-size:2rem;font-weight:800;">Rp 299rb<span style="font-size:0.9rem;font-weight:500;color:var(--c-muted);">/bln</span></h3>
                        <p class="text-muted" style="font-size:0.88rem;">Untuk bengkel berkembang</p>
                        <ul class="list-unstyled text-start mt-3" style="font-size:0.88rem;">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>10 user</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>3 cabang</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Semua modul</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>POS & Booking</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Laporan lengkap</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Reminder WA/Email</li>
                        </ul>
                        <a href="{{ route('login') }}" class="btn btn-primary w-100 mt-3" style="border-radius:10px;">Coba Sekarang</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border" style="border-radius:14px;">
                    <div class="card-body text-center p-4">
                        <span class="badge bg-dark mb-3" style="font-size:0.8rem;padding:0.4rem 0.8rem;">Enterprise</span>
                        <h3 style="font-size:2rem;font-weight:800;">Custom</h3>
                        <p class="text-muted" style="font-size:0.88rem;">Untuk jaringan bengkel besar</p>
                        <ul class="list-unstyled text-start mt-3" style="font-size:0.88rem;">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>User unlimited</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Cabang unlimited</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Source code full</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Custom branding</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>API & integrasi</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Priority support</li>
                        </ul>
                        <a href="https://wa.me/6281234567890" class="btn btn-outline-dark w-100 mt-3" style="border-radius:10px;">Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta">
    <div class="container">
        <h2>Siap mulai dengan {{ config('app.name') }}?</h2>
        <p>Masuk dengan akun demo dan jelajahi semua 85+ modul — data sample sudah disiapkan untuk dicoba.</p>
        <div style="display:flex;justify-content:center;gap:0.75rem;flex-wrap:wrap;">
            <a href="{{ route('login') }}" class="btn btn-primary">
                <i class="fas fa-arrow-right-to-bracket"></i> Login Sekarang
            </a>
            <a href="{{ route('docs.index') }}" class="btn btn-ghost">
                <i class="fas fa-book"></i> Baca Tutorial
            </a>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-grid">
            <div>
                <h5><i class="fas fa-wrench" style="color:var(--c-primary)"></i> {{ config('app.name') }}</h5>
                <p style="color:#94a3b8;font-size:0.9rem;margin:0 0 1rem;max-width:320px;">
                    Aplikasi manajemen bengkel modern, ringan, multi-cabang, dan mudah dipakai operator front-desk maupun teknisi lapangan.
                </p>
            </div>
            <div>
                <h5>Produk</h5>
                <ul>
                    <li><a href="#fitur">Fitur Utama</a></li>
                    <li><a href="#operasional">Teknisi & CRM</a></li>
                    <li><a href="#modul">Daftar Modul</a></li>
                    <li><a href="{{ route('docs.index') }}">Dokumentasi</a></li>
                </ul>
            </div>
            <div>
                <h5>Akses</h5>
                <ul>
                    <li><a href="{{ route('login') }}">Masuk</a></li>
                    <li><a href="{{ route('docs.index') }}">Tutorial</a></li>
                    <li><a href="{{ url('/sitemap.xml') }}">Sitemap</a></li>
                </ul>
            </div>
            <div>
                <h5>Kontak</h5>
                <ul>
                    <li>Jl. Siliwangi No. 88</li>
                    <li>Semarang</li>
                    <li>024-7612345</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div>© {{ date('Y') }} {{ config('app.name') }}. Semua hak dilindungi.</div>
            <div><a href="{{ route('docs.index') }}">Docs</a> · <a href="{{ route('login') }}">Login</a></div>
        </div>
    </div>
</footer>

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => config('app.name'),
    'description' => 'ERP Bengkel Modern — 80+ modul: 13-step workflow, multi-cabang, auto-accounting, inventory, POS, CRM, loyalty, dan laporan keuangan.',
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'inLanguage' => 'id-ID',
    'url' => url('/'),
    'offers' => [
        '@type' => 'Offer',
        'price' => '0',
        'priceCurrency' => 'IDR',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

@include('components.purchase-cta')

<script>
// Scroll reveal animation
(function() {
    const reveals = document.querySelectorAll('.feature, .mini-card, .module-tile, .timeline-step, .stat');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal', 'visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    reveals.forEach(el => el.classList.add('reveal'));
    reveals.forEach(el => observer.observe(el));
})();
</script>

</body>
</html>
