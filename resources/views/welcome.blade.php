<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Aplikasi Manajemen Bengkel Modern</title>
    <meta name="description" content="Aplikasi manajemen bengkel 44 modul: multi-cabang, POS, booking online, customer, kendaraan, jobcard, inspeksi, inventory parts, invoice, loyalty, klaim garansi, komisi, audit log, dan laporan keuangan dalam satu sistem.">
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
        <a href="/" class="brand"><i class="fas fa-wrench"></i> {{ config('app.name') }}</a>
        <div class="nav-links">
            <a href="#fitur" class="nav-link">Fitur Utama</a>
            <a href="#operasional" class="nav-link">Operasional</a>
            <a href="#marketing" class="nav-link">Marketing</a>
            <a href="#modul" class="nav-link">44 Modul</a>
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
                <span class="badge"><i class="fas fa-bolt"></i> 44 modul · multi-cabang · POS · Booking</span>
                <h1>Bengkel digital end-to-end, <span>satu aplikasi</span>.</h1>
                <p class="lead">
                    Customer, kendaraan, jobcard, inspeksi, sparepart, POS kasir, booking online,
                    invoice, loyalty, klaim garansi, audit log, sampai laporan keuangan — semua
                    saling terhubung dan siap pakai di banyak cabang.
                </p>
                <div class="hero-cta">
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Mulai Pakai
                    </a>
                    <a href="{{ route('docs.index') }}" class="btn btn-ghost">
                        <i class="fas fa-book"></i> Baca Tutorial
                    </a>
                </div>
                <div class="hero-meta">
                    <span><i class="fas fa-check-circle"></i> Multi-cabang siap pakai</span>
                    <span><i class="fas fa-check-circle"></i> Audit log lengkap</span>
                    <span><i class="fas fa-check-circle"></i> Reminder WA/Email otomatis</span>
                </div>
            </div>
            <div class="hero-img">
                <img src="{{ asset('images/features/dashboard.png') }}" alt="Dashboard {{ config('app.name') }}">
            </div>
        </div>

        <div class="stats">
            <div class="stat"><div class="v">44</div><div class="l">Modul Terintegrasi</div></div>
            <div class="stat"><div class="v">3</div><div class="l">Tipe Invoice</div></div>
            <div class="stat"><div class="v">6</div><div class="l">Kategori Inspeksi</div></div>
            <div class="stat"><div class="v">∞</div><div class="l">Cabang & Pengguna</div></div>
        </div>
    </div>
</section>

{{-- ====================== SECTION FITUR UTAMA ====================== --}}
<section id="fitur">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Fitur Utama</div>
            <h2>Inti operasional yang dipakai setiap hari</h2>
            <p>Dari customer datang sampai kendaraan keluar — semua langkah punya tempat sendiri di aplikasi.</p>
        </div>

        {{-- Customer --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-users"></i></div>
                <h3>Manajemen Pelanggan</h3>
                <p>Catat customer lengkap dengan kontak, perusahaan, dan NPWP. Lihat seluruh riwayat service & invoice per customer dalam satu halaman.</p>
                <ul class="feature-bullets">
                    <li>CRUD lengkap + import CSV massal</li>
                    <li>Riwayat semua kendaraan & service</li>
                    <li>Custom fields (tgl lahir, sumber customer, dll)</li>
                    <li>Dukungan customer perorangan & perusahaan</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/customers</span>
                </div>
                <img src="{{ asset('images/features/customers.png') }}" alt="Manajemen Customer">
            </div>
        </div>

        {{-- Vehicle --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-car"></i></div>
                <h3>Database Kendaraan</h3>
                <p>Daftarkan setiap kendaraan dengan plat unik, foto kondisi, dan riwayat servis lengkap. Master data jenis, merk, BBM, dan warna sudah siap pakai.</p>
                <ul class="feature-bullets">
                    <li>Plat nomor unik dengan validasi otomatis</li>
                    <li>Foto multi-angle (depan, samping, kerusakan)</li>
                    <li>Tracking odometer & rekomendasi service berikutnya</li>
                    <li>Mobil, motor, truk, bus — semua didukung</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/vehicles</span>
                </div>
                <img src="{{ asset('images/features/vehicles.png') }}" alt="Database Kendaraan">
            </div>
        </div>

        {{-- Service --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-tools"></i></div>
                <h3>Alur Service & Jobcard</h3>
                <p>Jantung aplikasi. Buat service dari customer + kendaraan, otomatis generate jobcard. Status transparan: Open → In Process → Done.</p>
                <ul class="feature-bullets">
                    <li>Assign ke teknisi, tracking siapa kerjakan apa</li>
                    <li>Kategori service: berkala, breakdown, repeat, garansi</li>
                    <li>Foto before / after untuk bukti & dokumentasi</li>
                    <li>Auto-link ke invoice ketika selesai</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/services</span>
                </div>
                <img src="{{ asset('images/features/services.png') }}" alt="Daftar Service">
            </div>
        </div>

        {{-- Jobcard & Inspection --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-clipboard-list"></i></div>
                <h3>Jobcard & Inspection Checklist</h3>
                <p>Setiap service punya satu jobcard dengan nomor unik, odometer masuk-keluar, dan checklist inspeksi 6 kategori (mesin, kaki-kaki, kelistrikan, dst).</p>
                <ul class="feature-bullets">
                    <li>Checklist inspeksi yang bisa di-custom</li>
                    <li>Library inspection points 30+ titik standar</li>
                    <li>Print jobcard untuk ditandatangani customer & teknisi</li>
                    <li>Rekomendasi service berikutnya (odo + tanggal)</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/jobcards</span>
                </div>
                <img src="{{ asset('images/features/jobcards.png') }}" alt="Daftar Jobcard">
            </div>
        </div>

        {{-- Invoice --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-file-invoice-dollar"></i></div>
                <h3>Invoice & Pembayaran Fleksibel</h3>
                <p>3 tipe invoice (service, sales, parts) dengan dukungan pembayaran cicil, kirim PDF, kirim via WhatsApp atau email langsung dari aplikasi.</p>
                <ul class="feature-bullets">
                    <li>Multiple payment methods (Cash, Transfer, QRIS, dll)</li>
                    <li>Status otomatis: Unpaid → Half Paid → Full Paid</li>
                    <li>Generate PDF profesional dengan logo bengkel</li>
                    <li>Histori pembayaran lengkap per invoice</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/invoices</span>
                </div>
                <img src="{{ asset('images/features/invoices.png') }}" alt="Daftar Invoice">
            </div>
        </div>

        {{-- Inventory --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill"><i class="fas fa-boxes-stacked"></i></div>
                <h3>Inventory & Stok Sparepart</h3>
                <p>Kelola sparepart dengan stok real-time. Otomatis berkurang ketika dipakai service, otomatis bertambah ketika purchase order diterima.</p>
                <ul class="feature-bullets">
                    <li>CRUD produk dengan harga beli & harga jual</li>
                    <li>Stock opname untuk koreksi hitung fisik</li>
                    <li>Audit trail setiap perubahan stok (siapa, kapan, alasannya)</li>
                    <li>Import CSV massal untuk migrasi awal</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/products</span>
                </div>
                <img src="{{ asset('images/features/products.png') }}" alt="Inventory Produk">
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION OPERASIONAL & MULTI-CABANG ====================== --}}
<section id="operasional" class="alt">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Operasional Skala Besar</div>
            <h2>Multi-cabang, slot service, dan reminder otomatis</h2>
            <p>Dirancang untuk bengkel yang tumbuh — banyak cabang, banyak slot service, dan banyak customer yang harus diingatkan.</p>
        </div>

        {{-- Multi-cabang --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill" style="background:#fef3c7;color:#d97706"><i class="fas fa-building"></i></div>
                <h3>Multi-Cabang dengan Switch Cepat</h3>
                <p>Punya lebih dari satu cabang? Daftarkan semuanya. Setiap cabang punya jam operasional, hari libur, dan washbay (slot service) sendiri. Operator bisa switch cabang dari topbar.</p>
                <ul class="feature-bullets">
                    <li>Daftar cabang dengan kode, alamat, kontak</li>
                    <li>Jam operasional per hari per cabang</li>
                    <li>Hari libur (sekali / berulang tahunan)</li>
                    <li>Branch switcher cepat di topbar</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/branches</span>
                </div>
                <img src="{{ asset('images/features/branches.png') }}" alt="Multi-Cabang">
            </div>
        </div>

        {{-- Washbay --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill" style="background:#dcfce7;color:#16a34a"><i class="fas fa-warehouse"></i></div>
                <h3>Washbay & Slot Service</h3>
                <p>Visualisasi slot fisik bengkel: kosong, sedang dipakai, atau maintenance. Tempatkan kendaraan ke slot saat dikerjakan teknisi, lepas saat selesai.</p>
                <ul class="feature-bullets">
                    <li>Status real-time: kosong / dipakai / maintenance</li>
                    <li>Tempelkan ke service yang sedang dikerjakan</li>
                    <li>Pemetaan per cabang</li>
                    <li>Visual card layout, sekali lihat langsung paham</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/washbays</span>
                </div>
                <img src="{{ asset('images/features/washbays.png') }}" alt="Washbay / Slot Service">
            </div>
        </div>

        {{-- Reminder --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill" style="background:#fce7f3;color:#db2777"><i class="fas fa-bell"></i></div>
                <h3>Reminder Otomatis ke Pelanggan</h3>
                <p>Sistem menemukan kendaraan yang sudah jatuh tempo service (berdasarkan odo atau tanggal) dan menyiapkan pesan reminder. Kirim batch ke WhatsApp atau email.</p>
                <ul class="feature-bullets">
                    <li>Template notifikasi yang bisa di-custom dengan variabel</li>
                    <li>Reminder ganti oli, MOT, tanggal STNK, ulang tahun</li>
                    <li>Kirim manual atau auto-scheduled</li>
                    <li>Email log untuk verifikasi keterkiriman</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/reminders</span>
                </div>
                <img src="{{ asset('images/features/reminders.png') }}" alt="Reminder Otomatis">
            </div>
        </div>

        {{-- Audit & Stock History --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-clock-rotate-left"></i></div>
                <h3>Audit Trail & Email Log</h3>
                <p>Setiap perubahan stok tercatat: siapa, kapan, alasan, qty sebelum & sesudah. Setiap email yang dikirim ada log-nya. Tidak ada lagi "tadi siapa yang ubah?".</p>
                <ul class="feature-bullets">
                    <li>Stock history per produk: in, out, adjust, opname</li>
                    <li>Email log lengkap dengan status delivered</li>
                    <li>Notes polymorphic di customer, kendaraan, service, dst</li>
                    <li>Compliance & rekonsiliasi jadi mudah</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/stock-histories</span>
                </div>
                <img src="{{ asset('images/features/stock-histories.png') }}" alt="Stock History / Audit Trail">
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION REPORT & ANALYTICS ====================== --}}
<section>
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Laporan & Analytics</div>
            <h2>Empat sudut pandang bisnis bengkel</h2>
            <p>Laporan service, sales, stok, dan keuangan — semua bisa di-export ke PDF & Excel.</p>
        </div>

        <div class="mini-grid">
            <div class="mini-card">
                <img src="{{ asset('images/features/reports.png') }}" alt="Financial Report">
                <div class="body">
                    <h4><i class="fas fa-chart-line"></i> Laporan Keuangan</h4>
                    <p>Profit/loss harian dan bulanan otomatis dari income, sales, service, & expense.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/reports-sales.png') }}" alt="Sales Report">
                <div class="body">
                    <h4><i class="fas fa-chart-bar"></i> Laporan Penjualan</h4>
                    <p>Volume penjualan, item terlaris, tren per periode — ekspor PDF maupun Excel.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/reports-stock.png') }}" alt="Stock Report">
                <div class="body">
                    <h4><i class="fas fa-warehouse"></i> Laporan Stok</h4>
                    <p>Stok berjalan, slow-moving items, dan rekomendasi reorder.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/reports-service.png') }}" alt="Service Report">
                <div class="body">
                    <h4><i class="fas fa-wrench"></i> Laporan Service</h4>
                    <p>Volume service per kategori dan per teknisi untuk evaluasi tim.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/incomes.png') }}" alt="Income">
                <div class="body">
                    <h4><i class="fas fa-arrow-trend-up"></i> Pemasukan</h4>
                    <p>Catat pemasukan non-service terpisah agar laporan profit akurat.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/expenses.png') }}" alt="Expense">
                <div class="body">
                    <h4><i class="fas fa-arrow-trend-down"></i> Pengeluaran</h4>
                    <p>Track gaji, listrik, sewa, dan biaya operasional dengan kategori & kuitansi.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/suppliers.png') }}" alt="Supplier">
                <div class="body">
                    <h4><i class="fas fa-truck"></i> Manajemen Supplier</h4>
                    <p>Catat semua supplier sparepart dengan kontak & history pembelian.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/purchases.png') }}" alt="Purchase Order">
                <div class="body">
                    <h4><i class="fas fa-cart-shopping"></i> Purchase Order</h4>
                    <p>Buat PO, tandai received → stok otomatis bertambah dengan trail history.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/gate-passes.png') }}" alt="Gate Pass">
                <div class="body">
                    <h4><i class="fas fa-ticket-alt"></i> Gate Pass</h4>
                    <p>Surat jalan keluar dengan stempel waktu masuk/keluar dan print PDF.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/sales.png') }}" alt="Sales POS">
                <div class="body">
                    <h4><i class="fas fa-cash-register"></i> Sales / POS Parts</h4>
                    <p>POS jual sparepart langsung tanpa service untuk walk-in customer.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/email-logs.png') }}" alt="Email Log">
                <div class="body">
                    <h4><i class="fas fa-envelope-open-text"></i> Email Log</h4>
                    <p>Verifikasi setiap email yang terkirim dengan status & isi pesan.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/custom-fields.png') }}" alt="Custom Fields">
                <div class="body">
                    <h4><i class="fas fa-puzzle-piece"></i> Custom Fields</h4>
                    <p>Tambah field tambahan di customer, kendaraan, service tanpa coding.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION MARKETING & LOYALTY ====================== --}}
<section id="marketing" class="alt">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">Marketing & Pelanggan Setia</div>
            <h2>Modul lengkap untuk menarik dan menahan customer</h2>
            <p>Lebih dari sekadar pencatat service — sistem ini punya POS, booking online, voucher, loyalty, dan review untuk mengubah bengkel jadi mesin retention.</p>
        </div>

        {{-- POS Counter --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill" style="background:#ecfdf5;color:#059669"><i class="fas fa-cash-register"></i></div>
                <h3>POS Kasir Real-Time</h3>
                <p>Terminal kasir khusus untuk transaksi cepat: scan barcode, klik produk, langsung bayar dan cetak struk. Cocok untuk walk-in customer yang beli oli atau sparepart eceran.</p>
                <ul class="feature-bullets">
                    <li>Scan barcode atau klik produk langsung</li>
                    <li>Multi-metode bayar + auto kembalian</li>
                    <li>Sesi kasir (buka–tutup) dengan rekap</li>
                    <li>Stok berkurang otomatis tiap transaksi</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/pos</span>
                </div>
                <img src="{{ asset('images/features/pos.png') }}" alt="POS Terminal Kasir">
            </div>
        </div>

        {{-- Booking --}}
        <div class="feature reverse">
            <div class="feature-text">
                <div class="icon-pill" style="background:#fef3c7;color:#d97706"><i class="fas fa-calendar-check"></i></div>
                <h3>Booking Online Customer</h3>
                <p>Customer pesan jadwal service dari handphone, operator tinggal konfirmasi. Tidak ada lagi customer datang tapi bengkel penuh, atau bengkel kosong tapi customer tidak tahu.</p>
                <ul class="feature-bullets">
                    <li>Pilih tanggal, jam, kendaraan, dan layanan</li>
                    <li>Konfirmasi → otomatis jadi service di sistem</li>
                    <li>Cek kapasitas washbay & teknisi sebelum approve</li>
                    <li>Notifikasi otomatis ke customer setelah dikonfirmasi</li>
                </ul>
            </div>
            <div class="feature-shot framed">
                <div class="browser-bar">
                    <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
                    <span class="url">/bookings</span>
                </div>
                <img src="{{ asset('images/features/bookings.png') }}" alt="Booking Online">
            </div>
        </div>

        {{-- Loyalty --}}
        <div class="feature">
            <div class="feature-text">
                <div class="icon-pill" style="background:#fce7f3;color:#db2777"><i class="fas fa-medal"></i></div>
                <h3>Loyalty Points & Membership Tier</h3>
                <p>Customer dapat poin dari setiap transaksi. Tier otomatis naik dari Bronze → Silver → Gold → Platinum. Customer balik untuk dapat reward.</p>
                <ul class="feature-bullets">
                    <li>Poin per Rupiah pembelian (rasio bisa di-set)</li>
                    <li>4 tier dengan benefit yang bisa di-custom</li>
                    <li>Leaderboard Top 5 customer per cabang</li>
                    <li>Redemption: poin → diskon atau hadiah</li>
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

        {{-- Voucher + Review (row 2 mini-cards) --}}
        <div class="mini-grid" style="margin-top:1rem;">
            <div class="mini-card">
                <img src="{{ asset('images/features/vouchers.png') }}" alt="Voucher Promo">
                <div class="body">
                    <h4><i class="fas fa-tags"></i> Voucher & Promo</h4>
                    <p>Generate kode promo dengan masa berlaku, max usage, dan diskon nominal atau persentase. Bisa dipakai di POS atau invoice.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/reviews.png') }}" alt="Reviews">
                <div class="body">
                    <h4><i class="fas fa-star"></i> Review Customer</h4>
                    <p>Kumpulkan rating & feedback dari customer setelah service. Konten ini juga jadi material untuk halaman SEO publik.</p>
                </div>
            </div>
            <div class="mini-card">
                <img src="{{ asset('images/features/warranty-claims.png') }}" alt="Warranty Claims">
                <div class="body">
                    <h4><i class="fas fa-shield-halved"></i> Klaim Garansi</h4>
                    <p>Catat klaim garansi dari customer, link ke parts/jobcard asal, dan tracking sampai diselesaikan.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ====================== SECTION HRM & TATA KELOLA ====================== --}}
<section id="hrm">
    <div class="container">
        <div class="sec-head">
            <div class="eyebrow">HRM & Tata Kelola</div>
            <h2>Kelola tim, hak akses, dan kas internal dengan rapi</h2>
            <p>Modul yang membuat bengkel siap di-audit: siapa boleh akses apa, siapa kerjakan apa, dan uang masuk-keluar dari kasir kecil.</p>
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
            <h2>44 modul siap pakai</h2>
            <p>Semua kebutuhan bengkel modern dalam satu instalasi — tidak ada add-on terpisah yang harus dibeli.</p>
        </div>

        <div class="modules">
            <div class="module-tile"><div class="ic"><i class="fas fa-gauge-high"></i></div><div><h5>Dashboard</h5><p>Ringkasan operasional real-time.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-users"></i></div><div><h5>Customer</h5><p>CRUD + import CSV + histori.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-car"></i></div><div><h5>Vehicle</h5><p>Plat unik + foto + odometer.</p></div></div>
            <div class="module-tile"><div class="ic"><i class="fas fa-tools"></i></div><div><h5>Service</h5><p>Workflow open → in process → done.</p></div></div>
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
            <div class="module-tile"><div class="ic"><i class="fas fa-chart-line"></i></div><div><h5>Reports</h5><p>4 jenis + ekspor PDF/Excel.</p></div></div>
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
            <h2>Dari kendaraan masuk sampai siap diserahkan</h2>
            <p>Satu alur sederhana yang mencerminkan kerja nyata di lapangan — tidak ada langkah yang berlebihan.</p>
        </div>

        <div class="timeline">
            <div class="timeline-step">
                <div class="num">1</div>
                <h4>Customer Datang</h4>
                <p>Operator catat customer (kalau baru) dan pilih kendaraan miliknya. Plat & odometer dicatat sebagai bukti kondisi masuk.</p>
            </div>
            <div class="timeline-step">
                <div class="num">2</div>
                <h4>Service & Jobcard</h4>
                <p>Buat service baru otomatis menghasilkan jobcard. Assign teknisi, tempatkan ke washbay, isi checklist inspeksi 6 kategori.</p>
            </div>
            <div class="timeline-step">
                <div class="num">3</div>
                <h4>Pengerjaan & Parts</h4>
                <p>Teknisi mulai kerja. Setiap sparepart dipakai otomatis kurangi stok. Foto after & catatan internal direkam.</p>
            </div>
            <div class="timeline-step">
                <div class="num">4</div>
                <h4>Invoice & Serah Terima</h4>
                <p>Generate invoice, terima pembayaran, cetak gate pass. Reminder service berikutnya otomatis di-set untuk WA/email.</p>
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
            <p>Gunakan kredensial di bawah untuk login dan eksplorasi semua 44 modul {{ config('app.name') }}.</p>
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
        <p>Masuk dengan akun demo dan jelajahi semua 44 modul — data sample sudah disiapkan untuk dicoba.</p>
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
                    <li><a href="#operasional">Operasional</a></li>
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
    'name' => '{{ config('app.name') }}',
    'description' => 'Aplikasi manajemen bengkel multi-cabang: customer, kendaraan, jobcard, inspeksi, inventory parts, invoice, reminder otomatis, audit log, dan laporan keuangan.',
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
