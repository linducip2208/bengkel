@extends('pseo._layout')
@section('content')
<section class="content">
    <h2>{{ $t['city_title'] }}</h2>

    <div class="lang-switcher" style="margin-bottom:20px;display:flex;gap:8px;">
        <a href="/id/bengkel-{{ $city }}" style="padding:4px 12px;border-radius:6px;background:#e2e8f0;color:#1e293b;text-decoration:none;font-size:13px;font-weight:600;">🇮🇩 ID</a>
        <a href="/en/bengkel-{{ $city }}" style="padding:4px 12px;border-radius:6px;background:#e2e8f0;color:#1e293b;text-decoration:none;font-size:13px;font-weight:600;">🇬🇧 EN</a>
        <a href="/de/bengkel-{{ $city }}" style="padding:4px 12px;border-radius:6px;background:#e2e8f0;color:#1e293b;text-decoration:none;font-size:13px;font-weight:600;">🇩🇪 DE</a>
    </div>

    <p>{{ str_replace(['{city}'],[$cityName],$t['city_desc']) }}</p>

    @if($kelurahans->isNotEmpty())
    <h3>{{ __('Area Layanan di') }} {{ $cityName }}</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px;">
        @foreach($kelurahans as $k)
            <a href="/{{ $lang }}/bengkel-{{ $city }}/{{ $k->slug }}"
               style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;color:#1e293b;font-size:14px;">
                {{ $k->name }}
                <small style="color:#64748b;display:block;">{{ $k->kecamatan }}</small>
            </a>
        @endforeach
    </div>
    @endif

    <h3>{{ __('Layanan Bengkel Kami') }}</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;">
        @foreach($services as $svc)
            <a href="/{{ $lang }}/service-{{ $svc }}-{{ $city }}"
               style="padding:10px;background:#f1f5f9;border-radius:8px;text-decoration:none;color:#334155;font-weight:600;font-size:14px;">
                {{ ucwords(str_replace('-', ' ', $svc)) }}
            </a>
        @endforeach
    </div>

    <h3>{{ __('Spesialis Brand Mobil') }}</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;">
        @foreach($brands as $brand)
            <a href="/{{ $lang }}/bengkel-{{ $brand }}-{{ $city }}"
               style="padding:10px;background:#f1f5f9;border-radius:8px;text-decoration:none;color:#334155;font-weight:600;font-size:14px;">
                {{ ucfirst($brand) }}
            </a>
        @endforeach
    </div>

    <div style="margin-top:32px;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;padding:24px;border-radius:12px;text-align:center;">
        <h3 style="color:#fff;margin-top:0;">{{ __('Butuh Source Code Aplikasi Bengkel?') }}</h3>
        <p style="opacity:0.9;">Dapatkan source code lengkap aplikasi bengkel profesional. Full Laravel + MySQL, bisa di-custom sesuai kebutuhan bisnis Anda.</p>
        <a href="https://wa.me/6281296052010?text=Halo%2C%20saya%20tertarik%20source%20code%20aplikasi%20bengkel%20untuk%20{{ urlencode($cityName) }}"
           style="display:inline-block;background:#25D366;color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:700;font-size:16px;margin-top:8px;">
            💬 Chat WhatsApp
        </a>
    </div>
</section>
@endsection

<x-whatsapp-cta message="Halo {{ config('app.name') }}, saya di {{ $cityName }} butuh info bengkel." />
