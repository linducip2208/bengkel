<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --brand: #2563eb;
            --brand-dark: #1d4ed8;
            --brand-light: #eff6ff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            background: #f8fafc;
        }
        .font-display { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', 'Cascadia Code', monospace; }

        .login-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* Left: Hero Panel */
        .hero-panel {
            display: none;
            position: relative;
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 50%, #0f172a 100%);
            padding: 3rem;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        .hero-panel::before {
            content: ''; position: absolute; inset: 0;
            opacity: 0.25;
            background:
                radial-gradient(600px 300px at 20% 30%, #3b82f6 0%, transparent 50%),
                radial-gradient(500px 350px at 80% 70%, #6366f1 0%, transparent 50%);
        }
        .hero-panel .deco-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
        .hero-panel .deco-circle:nth-child(2) { width: 320px; height: 320px; top: -80px; right: -60px; }
        .hero-panel .deco-circle:nth-child(3) { width: 220px; height: 220px; bottom: 40px; left: -40px; }
        .hero-panel .large-icon {
            position: absolute; bottom: -40px; right: -30px;
            font-size: 16rem; opacity: 0.08; color: #fff;
        }
        .hero-panel .brand-row {
            position: relative; display: flex; align-items: center; gap: 0.6rem; color: #fff;
        }
        .hero-panel .brand-row .brand-icon { font-size: 1.6rem; }
        .hero-panel .brand-row .brand-name { font-weight: 800; font-size: 1.3rem; letter-spacing: -0.02em; }
        .hero-panel .tagline-block { position: relative; color: #fff; }
        .hero-panel .tagline-block h2 { font-size: 2.6rem; font-weight: 800; line-height: 1.15; margin-bottom: 0.8rem; letter-spacing: -0.02em; }
        .hero-panel .tagline-block p { color: rgba(255,255,255,0.8); font-size: 1.02rem; line-height: 1.6; max-width: 400px; margin-bottom: 2rem; }
        .hero-panel .benefit-cards {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; max-width: 420px;
        }
        .hero-panel .benefit-card {
            background: rgba(255,255,255,0.1); backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.12); border-radius: 12px;
            padding: 0.85rem 0.75rem; text-align: center;
        }
        .hero-panel .benefit-card i { font-size: 1.25rem; margin-bottom: 0.35rem; display: block; }
        .hero-panel .benefit-card span { font-size: 0.75rem; font-weight: 600; }
        .copyright { position: relative; color: rgba(255,255,255,0.45); font-size: 0.75rem; }

        /* Right: Form Panel */
        .form-panel {
            display: flex; align-items: center; justify-content: center; padding: 2.5rem 2rem;
        }
        .form-panel .form-wrapper { width: 100%; max-width: 420px; }
        .form-panel h1 { font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.3rem; letter-spacing: -0.02em; }
        .form-panel .subtitle { color: #64748b; margin-bottom: 1.75rem; font-size: 0.92rem; }
        .form-panel .subtitle a { color: var(--brand); font-weight: 600; }
        .form-panel .subtitle a:hover { text-decoration: underline; }

        .form-group { margin-bottom: 1.1rem; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 600; color: #334155; margin-bottom: 0.3rem; }
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%; padding: 0.65rem 0.9rem;
            border: 1.5px solid #cbd5e1; border-radius: 12px;
            font-size: 0.94rem; font-family: inherit;
            transition: all 0.15s; background: #fff;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
        }
        .form-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
        .form-row label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.84rem; color: #475569; cursor: pointer; }
        .form-row input[type="checkbox"] { accent-color: var(--brand); width: 15px; height: 15px; }
        .form-row a { font-size: 0.84rem; color: var(--brand); font-weight: 600; }

        .btn-login {
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
            width: 100%; padding: 0.75rem;
            background: linear-gradient(135deg, var(--brand) 0%, #6366f1 100%);
            color: #fff; border: none; border-radius: 12px;
            font-size: 0.98rem; font-weight: 700; font-family: inherit;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(37,99,235,0.35);
            transition: all 0.15s;
        }
        .btn-login:hover { box-shadow: 0 6px 20px rgba(37,99,235,0.45); transform: translateY(-1px); }

        .divider {
            display: flex; align-items: center; gap: 0.75rem;
            margin: 1.5rem 0; color: #94a3b8; font-size: 0.82rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }

        .demo-box {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.15rem;
        }
        .demo-box .demo-title { font-weight: 700; color: #334155; font-size: 0.88rem; margin-bottom: 0.5rem; }
        .demo-box .demo-row {
            font-size: 0.76rem; color: #475569; padding: 0.2rem 0;
            font-family: 'JetBrains Mono', 'Cascadia Code', monospace;
        }
        .demo-box .demo-row .role { font-weight: 700; }

        .alert {
            background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px;
            color: #991b1b; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.84rem;
        }

        @media (min-width: 1024px) {
            .hero-panel { display: flex; }
        }

        @media (max-width: 1023px) {
            .login-grid { grid-template-columns: 1fr; }
            .form-panel { padding: 2rem 1.5rem; }
        }

        @media (max-width: 640px) {
            .form-panel { padding: 1.5rem 1rem; }
            .form-panel h1 { font-size: 1.6rem; }
            .form-panel .form-wrapper { max-width: 100%; }
            .btn-login { min-height: 44px; }
            .demo-box .demo-row { font-size: 0.7rem; }
        }

        @media (pointer: coarse) {
            .btn-login { min-height: 48px; }
            .form-group input { min-height: 44px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body>

<div class="login-grid">
    {{-- Left: Hero Brand Panel --}}
    <div class="hero-panel">
        <div class="deco-circle"></div>
        <div class="deco-circle"></div>
        <div class="large-icon"><i class="fas fa-wrench"></i></div>

        <div class="brand-row">
            @if(!empty($appSettings['logo']))
                <img src="{{ asset('storage/' . $appSettings['logo']) }}" alt="Logo" class="brand-icon" style="height:32px;width:auto;">
            @else
                <i class="fas fa-wrench brand-icon"></i>
            @endif
            <span class="brand-name">{{ $appSettings['name'] ?? config('app.name') }}</span>
        </div>

        <div class="tagline-block">
            <h2>Garage Management,<br>Reinvented.</h2>
            <p>Kelola customer, kendaraan, jobcard, inventory, invoice, dan keuangan bengkel dalam satu aplikasi. Multi-cabang, POS, booking online — semua terintegrasi.</p>
            <div class="benefit-cards">
                <div class="benefit-card">
                    <i class="fas fa-building"></i>
                    <span>Multi-Cabang</span>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-cash-register"></i>
                    <span>POS Kasir</span>
                </div>
                <div class="benefit-card">
                    <i class="fas fa-chart-line"></i>
                    <span>Laporan Lengkap</span>
                </div>
            </div>
        </div>

        <div class="copyright">&copy; {{ date('Y') }} {{ config('app.name') }} &middot; Powered by Laravel</div>
    </div>

    {{-- Right: Login Form --}}
    <div class="form-panel">
        <div class="form-wrapper">
            <h1>Masuk</h1>
            <p class="subtitle">
                Belum punya akun? <a href="#">Hubungi admin</a>
            </p>

            @if ($errors->any())
            <div class="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password">
                </div>
                <div class="form-row">
                    <label>
                        <input type="checkbox" name="remember"> Ingat saya
                    </label>
                    <a href="#">Lupa password?</a>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </button>
            </form>

            <div class="divider">atau</div>

            <div class="demo-box">
                <div class="demo-title"><i class="fas fa-flask"></i> Demo Login</div>
                <div class="demo-row"><span class="role">Admin:</span> admin@bengkel.test / password</div>
                <div class="demo-row"><span class="role">Manager:</span> manager@bengkel.test / password</div>
                <div class="demo-row"><span class="role">Kasir:</span> kasir@bengkel.test / password</div>
                <div class="demo-row"><span class="role">Teknisi:</span> teknisi@bengkel.test / password</div>
                <div class="demo-row"><span class="role">Sales:</span> sales@bengkel.test / password</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
