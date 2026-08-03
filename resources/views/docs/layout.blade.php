<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dokumentasi') — {{ $appName }}</title>
    <meta name="description" content="@yield('description', 'Panduan lengkap menggunakan aplikasi ' . $appName . ' mulai dari setup awal sampai laporan.')">
    <link rel="canonical" href="{{ request()->url() }}">
    <meta property="og:title" content="@yield('title', 'Dokumentasi')">
    <meta property="og:description" content="@yield('description', 'Panduan lengkap menggunakan ' . $appName)">
    <meta property="og:type" content="article">
    <meta name="twitter:card" content="summary_large_image">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --docs-sidebar: 280px;
            --docs-top: 60px;
            --c-primary: #2563eb;
            --c-ink: #0f172a;
            --c-muted: #475569;
            --c-bg: #f8fafc;
            --c-line: #e2e8f0;
        }

        html { scroll-behavior: smooth; scroll-padding-top: 80px; }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: #fff;
            color: var(--c-ink);
            line-height: 1.65;
        }

        .docs-topbar {
            position: sticky;
            top: 0;
            height: var(--docs-top);
            background: #fff;
            border-bottom: 1px solid var(--c-line);
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            z-index: 100;
            gap: 1rem;
        }
        .docs-topbar .brand {
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--c-ink);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .docs-topbar .brand i { color: var(--c-primary); }
        .docs-topbar .sep { color: var(--c-muted); }
        .docs-topbar .crumb { color: var(--c-muted); font-size: 0.92rem; }
        .docs-topbar .actions { margin-left: auto; display: flex; gap: 0.5rem; }
        .docs-topbar .actions a {
            color: var(--c-muted);
            text-decoration: none;
            font-size: 0.88rem;
            padding: 0.4rem 0.85rem;
            border-radius: 6px;
            border: 1px solid var(--c-line);
        }
        .docs-topbar .actions a:hover { background: var(--c-bg); color: var(--c-ink); }

        .docs-shell {
            display: grid;
            grid-template-columns: var(--docs-sidebar) 1fr;
            min-height: calc(100vh - var(--docs-top));
        }

        .docs-sidebar {
            background: var(--c-bg);
            border-right: 1px solid var(--c-line);
            padding: 1.5rem 0;
            position: sticky;
            top: var(--docs-top);
            height: calc(100vh - var(--docs-top));
            overflow-y: auto;
        }
        .docs-sidebar h6 {
            padding: 0 1.5rem 0.5rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--c-muted);
            margin: 0;
        }
        .docs-toc {
            list-style: none;
            margin: 0;
            padding: 0.25rem 0;
        }
        .docs-toc a {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.5rem 1.5rem;
            color: var(--c-muted);
            text-decoration: none;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
        }
        .docs-toc a:hover { background: #eef2ff; color: var(--c-ink); }
        .docs-toc a.active {
            background: #eff6ff;
            color: var(--c-primary);
            border-left-color: var(--c-primary);
            font-weight: 600;
        }
        .docs-toc i { width: 18px; text-align: center; font-size: 0.85rem; }

        .docs-main {
            padding: 2.5rem 3rem;
            max-width: 920px;
        }
        .docs-main h1 {
            font-size: 2.1rem;
            font-weight: 800;
            margin: 0 0 0.5rem;
            letter-spacing: -0.02em;
        }
        .docs-main .lead {
            color: var(--c-muted);
            font-size: 1.05rem;
            margin-bottom: 2rem;
        }
        .docs-main section { margin-bottom: 3rem; }
        .docs-main section h2 {
            font-size: 1.45rem;
            font-weight: 700;
            margin: 0 0 0.75rem;
            padding-top: 1rem;
        }
        .docs-main section h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 1.5rem 0 0.5rem;
            color: var(--c-ink);
        }
        .docs-main p { margin-bottom: 0.85rem; color: #334155; }
        .docs-main ol, .docs-main ul { padding-left: 1.4rem; margin-bottom: 1rem; }
        .docs-main ol li, .docs-main ul li { margin-bottom: 0.4rem; color: #334155; }

        .docs-callout {
            border-left: 4px solid var(--c-primary);
            background: #eff6ff;
            padding: 0.85rem 1.1rem;
            border-radius: 6px;
            margin: 1rem 0;
            color: #1e3a8a;
        }
        .docs-callout.tip { border-color: #10b981; background: #ecfdf5; color: #065f46; }
        .docs-callout.note { border-color: #f59e0b; background: #fffbeb; color: #78350f; }
        .docs-callout.warn { border-color: #ef4444; background: #fef2f2; color: #7f1d1d; }
        .docs-callout::before {
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            margin-right: 0.5rem;
        }
        .docs-callout.tip::before { content: '\f0eb'; }
        .docs-callout.note::before { content: '\f06a'; }
        .docs-callout.warn::before { content: '\f071'; }

        .docs-credentials {
            border: 1px solid var(--c-line);
            border-radius: 10px;
            padding: 1rem 1.15rem;
            background: #fff;
            margin: 0.75rem 0 1rem;
        }
        .docs-cred-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        .docs-cred-table th, .docs-cred-table td {
            padding: 0.55rem 0.8rem;
            border-bottom: 1px solid var(--c-line);
            text-align: left;
            vertical-align: middle;
        }
        .docs-cred-table thead th {
            background: var(--c-bg);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--c-muted);
        }
        .docs-cred-table tbody tr:last-child td { border-bottom: 0; }
        .docs-cred-table code {
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 600;
        }
        .docs-cred-note {
            margin: 0.75rem 0 0;
            font-size: 0.88rem;
            color: var(--c-muted);
        }

        .docs-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5rem 0 1rem;
            font-size: 0.94rem;
        }
        .docs-table th, .docs-table td {
            padding: 0.6rem 0.9rem;
            border-bottom: 1px solid var(--c-line);
            text-align: left;
            vertical-align: top;
        }
        .docs-table tr:first-child td { background: var(--c-bg); font-weight: 600; }

        .docs-faq-item {
            border: 1px solid var(--c-line);
            border-radius: 8px;
            padding: 1rem 1.15rem;
            margin-bottom: 0.75rem;
            background: #fff;
        }
        .docs-faq-item strong {
            display: block;
            color: var(--c-ink);
            margin-bottom: 0.35rem;
        }
        .docs-faq-item p { margin: 0; color: var(--c-muted); }

        .docs-pager {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--c-line);
        }
        .docs-pager a {
            flex: 1;
            padding: 0.85rem 1rem;
            border: 1px solid var(--c-line);
            border-radius: 8px;
            text-decoration: none;
            color: var(--c-ink);
            background: #fff;
        }
        .docs-pager a:hover { border-color: var(--c-primary); background: #f8fafc; }
        .docs-pager .label { font-size: 0.75rem; color: var(--c-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .docs-pager .title { font-weight: 600; font-size: 0.95rem; }
        .docs-pager .next { text-align: right; }

        .docs-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 0.85rem;
            margin: 1rem 0 2rem;
        }
        .docs-card {
            border: 1px solid var(--c-line);
            border-radius: 10px;
            padding: 1rem 1.15rem;
            background: #fff;
            text-decoration: none;
            color: inherit;
            transition: all 0.15s;
        }
        .docs-card:hover {
            border-color: var(--c-primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
            transform: translateY(-1px);
        }
        .docs-card .icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #eff6ff;
            color: var(--c-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            margin-bottom: 0.6rem;
        }
        .docs-card .t { font-weight: 700; margin-bottom: 0.25rem; color: var(--c-ink); }
        .docs-card .d { font-size: 0.85rem; color: var(--c-muted); }

        code {
            background: #f1f5f9;
            color: #be185d;
            padding: 0.12rem 0.4rem;
            border-radius: 4px;
            font-size: 0.88em;
        }

        @media (max-width: 900px) {
            .docs-shell { grid-template-columns: 1fr; }
            .docs-sidebar {
                position: static;
                height: auto;
                border-right: 0;
                border-bottom: 1px solid var(--c-line);
            }
            .docs-main { padding: 1.5rem 1.25rem; }
        }

        /* ===== Screenshot Blocks ===== */
        .docs-screenshot {
            margin: 1.5rem 0 0.5rem;
            border: 1px solid var(--c-line);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .docs-screenshot.plain { border-radius: 10px; }
        .docs-screenshot.plain img { border-radius: 10px; }
        .docs-screenshot .browser-chrome {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 0.9rem;
            background: #f1f5f9;
            border-bottom: 1px solid var(--c-line);
        }
        .docs-screenshot .browser-chrome .dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: #cbd5e1;
        }
        .docs-screenshot .browser-chrome .dot.r { background: #fca5a5; }
        .docs-screenshot .browser-chrome .dot.y { background: #fcd34d; }
        .docs-screenshot .browser-chrome .dot.g { background: #86efac; }
        .docs-screenshot .browser-chrome .url {
            margin-left: 0.6rem; font-size: 0.78rem; color: var(--c-muted);
            background: #fff; padding: 0.15rem 0.6rem; border-radius: 4px;
            border: 1px solid var(--c-line); flex: 1; max-width: 400px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .docs-screenshot img {
            display: block; width: 100%; height: auto;
        }
        .docs-screenshot-caption {
            font-size: 0.85rem;
            color: var(--c-muted);
            font-style: italic;
            margin: 0.3rem 0 1.5rem;
            text-align: center;
        }

        /* ===== Popup Kontak Support ===== */
        .support-popup-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(6px);
            z-index: 9998;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            animation: backdropIn 0.3s ease-out;
        }
        .support-popup-backdrop.show { display: flex; }

        @keyframes backdropIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .support-popup {
            position: relative;
            width: 100%;
            max-width: 540px;
            background: linear-gradient(135deg, #f97316 0%, #ec4899 50%, #8b5cf6 100%);
            border-radius: 22px;
            padding: 2.5rem 2rem 2rem;
            color: #fff;
            text-align: center;
            box-shadow: 0 25px 60px -10px rgba(236, 72, 153, 0.55), 0 0 0 1px rgba(255,255,255,0.12);
            animation: popupIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }

        .support-popup::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.18) 0%, transparent 50%);
            animation: shimmer 10s linear infinite;
            pointer-events: none;
        }

        @keyframes shimmer {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes popupIn {
            from { opacity: 0; transform: scale(0.7) translateY(40px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .support-popup .close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            border: 0;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            color: #fff;
            font-size: 1.1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 2;
        }
        .support-popup .close-btn:hover {
            background: rgba(255,255,255,0.35);
            transform: rotate(90deg);
        }

        .support-popup .icon-wa {
            position: relative;
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
            animation: bounce 2s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        .support-popup h3 {
            position: relative;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0 0 0.5rem;
            letter-spacing: -0.02em;
            z-index: 1;
        }

        .support-popup .subtitle {
            position: relative;
            font-size: 0.98rem;
            opacity: 0.95;
            margin-bottom: 1.25rem;
            z-index: 1;
        }

        .support-popup .phone-number {
            position: relative;
            font-size: 2.6rem;
            font-weight: 900;
            letter-spacing: 0.02em;
            margin: 0.75rem 0 1.25rem;
            text-shadow: 0 2px 12px rgba(0,0,0,0.2);
            z-index: 1;
            font-variant-numeric: tabular-nums;
        }

        .support-popup .cta-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: #fff;
            color: #db2777;
            padding: 0.9rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05rem;
            box-shadow: 0 8px 20px -4px rgba(0,0,0,0.25);
            transition: all 0.25s;
            z-index: 1;
        }
        .support-popup .cta-btn:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 12px 28px -4px rgba(0,0,0,0.3);
            color: #be185d;
        }
        .support-popup .cta-btn i { font-size: 1.2rem; }

        .support-popup .footnote {
            position: relative;
            margin-top: 1rem;
            font-size: 0.8rem;
            opacity: 0.88;
            z-index: 1;
        }

        @media (max-width: 540px) {
            .support-popup { padding: 2rem 1.25rem 1.5rem; }
            .support-popup .phone-number { font-size: 2.1rem; }
            .support-popup h3 { font-size: 1.3rem; }
            .support-popup .icon-wa { font-size: 2.8rem; }
        }
    </style>
</head>
<body>

<header class="docs-topbar">
    <a href="{{ route('docs.index') }}" class="brand">
        <i class="fas fa-book"></i> {{ $appName }} Docs
    </a>
    <span class="sep">/</span>
    <span class="crumb">@yield('crumb', 'Beranda')</span>
    <div class="actions">
        <a href="{{ url('/') }}"><i class="fas fa-arrow-right-from-bracket me-1"></i>Masuk Aplikasi</a>
    </div>
</header>

<div class="docs-shell">
    <aside class="docs-sidebar">
        <h6>Daftar Isi</h6>
        <ul class="docs-toc">
            <li>
                <a href="{{ route('docs.index') }}" class="{{ request()->routeIs('docs.index') ? 'active' : '' }}">
                    <i class="fas fa-house"></i> Beranda Docs
                </a>
            </li>
            @foreach($sections as $s)
                <li>
                    <a href="{{ route('docs.show', $s['slug']) }}"
                       class="{{ request()->routeIs('docs.show') && (request()->route('slug') === $s['slug']) ? 'active' : '' }}">
                        <i class="fas {{ $s['icon'] }}"></i> {{ $s['title'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>

    <main class="docs-main">
        @yield('content')
    </main>
</div>

<div class="support-popup-backdrop" id="supportPopup" role="dialog" aria-modal="true" aria-labelledby="supportPopupTitle">
    <div class="support-popup">
        <button type="button" class="close-btn" onclick="closeSupportPopup()" aria-label="Tutup">
            <i class="fas fa-times"></i>
        </button>
        <div class="icon-wa"><i class="fab fa-whatsapp"></i></div>
        <h3 id="supportPopupTitle">Butuh Bantuan?</h3>
        <p class="subtitle">Tim support kami siap bantu setup, training, dan troubleshoot aplikasi.</p>
        <div class="phone-number">081296052010</div>
        <a href="https://wa.me/6281296052010?text=Halo%20admin%2C%20saya%20butuh%20bantuan%20tentang%20Bengkel%20Paten."
           class="cta-btn" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp"></i> Chat WhatsApp Sekarang
        </a>
        <div class="footnote">Jam kerja: Senin–Sabtu, 08.00–17.00 WIB</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function() {
        const POPUP_KEY = 'bengkelDocsSupportPopup_v1';
        const popup = document.getElementById('supportPopup');

        function closePopup() {
            popup.classList.remove('show');
            try { localStorage.setItem(POPUP_KEY, '1'); } catch (e) {}
        }
        window.closeSupportPopup = closePopup;

        // Tutup saat klik backdrop di luar card
        popup.addEventListener('click', function(e) {
            if (e.target === popup) closePopup();
        });

        // Tutup dengan Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && popup.classList.contains('show')) closePopup();
        });

        // Tampilkan sekali saja per browser
        let alreadyShown = false;
        try { alreadyShown = localStorage.getItem(POPUP_KEY) === '1'; } catch (e) {}

        if (!alreadyShown) {
            // Delay sebentar supaya halaman render dulu
            setTimeout(function() { popup.classList.add('show'); }, 800);
        }
    })();
</script>
</body>
</html>
