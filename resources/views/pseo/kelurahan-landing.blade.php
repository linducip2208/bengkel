@extends('pseo._layout')
@section('content')
<section class="content">
    <h2>{{ str_replace(['{kelurahan}','{city}'],[$kelurahanName,$cityName],$t['kel_title']) }}</h2>

    <div class="lang-switcher" style="margin-bottom:20px;display:flex;gap:8px;">
        <a href="/id/bengkel-{{ $city }}/{{ $kelurahan }}" style="padding:4px 12px;border-radius:6px;background:#e2e8f0;color:#1e293b;text-decoration:none;font-size:13px;font-weight:600;">🇮🇩 ID</a>
        <a href="/en/bengkel-{{ $city }}/{{ $kelurahan }}" style="padding:4px 12px;border-radius:6px;background:#e2e8f0;color:#1e293b;text-decoration:none;font-size:13px;font-weight:600;">🇬🇧 EN</a>
        <a href="/de/bengkel-{{ $city }}/{{ $kelurahan }}" style="padding:4px 12px;border-radius:6px;background:#e2e8f0;color:#1e293b;text-decoration:none;font-size:13px;font-weight:600;">🇩🇪 DE</a>
    </div>

    <p>{{ str_replace(['{kelurahan}','{city}'],[$kelurahanName,$cityName],$t['kel_desc']) }}</p>

    @if($kecamatan)
    <p><strong>Kecamatan:</strong> {{ $kecamatan }} &middot; <strong>Kota:</strong> {{ $cityName }}</p>
    @endif

    <h3>Layanan Bengkel di {{ $kelurahanName }}</h3>
    <ul>
        <li>Servis berkala & tune-up</li>
        <li>Ganti oli & filter</li>
        <li>Servis AC mobil</li>
        <li>Perbaikan rem & kaki-kaki</li>
        <li>Ganti ban & spooring balancing</li>
        <li>Servis kelistrikan & aki</li>
        <li>Body repair & cat</li>
        <li>Overhaul mesin</li>
    </ul>

    <h3>Kenapa Pilih Bengkel Kami?</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;">
        <div style="padding:16px;background:#f0fdf4;border-radius:10px;"><strong>Teknisi Bersertifikasi</strong><p style="margin:4px 0 0;font-size:14px;color:#475569;">Mekanik berpengalaman dengan training rutin.</p></div>
        <div style="padding:16px;background:#eff6ff;border-radius:10px;"><strong>Sparepart Original</strong><p style="margin:4px 0 0;font-size:14px;color:#475569;">Garansi resmi pabrikan.</p></div>
        <div style="padding:16px;background:#fefce8;border-radius:10px;"><strong>Harga Transparan</strong><p style="margin:4px 0 0;font-size:14px;color:#475569;">Estimasi biaya jelas sebelum servis.</p></div>
        <div style="padding:16px;background:#fdf2f8;border-radius:10px;"><strong>Lokasi Strategis</strong><p style="margin:4px 0 0;font-size:14px;color:#475569;">Mudah dijangkau dari {{ $kelurahanName }}.</p></div>
    </div>

    <div style="margin-top:32px;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;padding:24px;border-radius:12px;text-align:center;">
        <h3 style="color:#fff;margin-top:0;">Source Code Aplikasi Bengkel</h3>
        <p style="opacity:0.9;">Punya aplikasi bengkel sendiri untuk area {{ $kelurahanName }}, {{ $cityName }}. Full source code Laravel, siap custom.</p>
        <a href="https://wa.me/6281296052010?text=Halo%2C%20saya%20di%20{{ urlencode($kelurahanName) }}%20{{ urlencode($cityName) }}%20butuh%20info%20aplikasi%20bengkel"
           style="display:inline-block;background:#25D366;color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:700;font-size:16px;margin-top:8px;">
            💬 Chat WhatsApp
        </a>
    </div>
</section>
@endsection

<x-whatsapp-cta message="Halo, saya di {{ $kelurahanName }}, {{ $cityName }} butuh servis mobil." />
