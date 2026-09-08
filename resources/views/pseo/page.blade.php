@extends('pseo._layout')

@push('head')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@section('content')
<div class="pseo-wrap">
    <nav class="pseo-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">Beranda</a><span aria-hidden="true">/</span><span>{{ $page->h1 }}</span>
    </nav>

    @include('components.pseo.hero', ['page' => $page])

    <section class="pseo-split-section" id="tentang">
        <div>
            <div class="pseo-eyebrow">KENAPA INI PENTING</div>
            <h2>{{ $page->problem }}</h2>
            <p>{{ $page->solution }}</p>
            <p style="margin-top:16px">{{ $page->audience }}</p>
        </div>
        <div class="pseo-benefit-list">
            @foreach($page->benefits as $benefit)
                <div><span>✓</span><strong>{{ $benefit }}</strong></div>
            @endforeach
        </div>
    </section>

    @include('components.pseo.feature-grid', ['page' => $page])
    @include('components.pseo.workflow')

    <section class="pseo-section" x-data="{ active: 0 }">
        <div class="pseo-section-heading">
            <div class="pseo-eyebrow">LIHAT TAMPILAN APLIKASI</div>
            <h2>Antarmuka nyata untuk proses yang nyata</h2>
            <p>Gambaran dari area aplikasi yang membantu tim bekerja setiap hari. Tampilan ini berasal dari aset aplikasi yang tersedia, bukan mockup marketing.</p>
        </div>
        <div class="pseo-demo-grid" style="display:grid;grid-template-columns:1.35fr .65fr;gap:20px;align-items:stretch">
            <div class="pseo-hero-card" style="transform:none;padding:10px"><img :src="active === 0 ? '{{ asset('marketing/screens/dashboard.png') }}' : '{{ asset('marketing/screens/products.png') }}'" src="{{ asset('marketing/screens/dashboard.png') }}" alt="Tampilan aplikasi bengkel" loading="lazy" width="900" height="560"></div>
            <div style="display:grid;gap:10px">
                <button type="button" class="pseo-related-card" :style="active === 0 ? 'border-color:#0f8b8d' : ''" @click="active = 0"><span>Dashboard</span><span>→</span></button>
                <button type="button" class="pseo-related-card" :style="active === 1 ? 'border-color:#0f8b8d' : ''" @click="active = 1"><span>Inventory & Product</span><span>→</span></button>
                <p style="padding:8px 4px;color:var(--muted);font-size:13px">Untuk demo lengkap sesuai role dan alur usaha Anda, gunakan CTA WhatsApp di halaman ini.</p>
            </div>
        </div>
    </section>

    @include('components.pseo.source-code-benefits', ['page' => $page])
    @include('components.pseo.faq', ['page' => $page])
    @include('components.pseo.related-pages', ['page' => $page])

    <section class="pseo-final-cta">
        <div class="pseo-eyebrow" style="color:#a9d9ca">SIAP MEMULAI?</div>
        <h2>Bahas kebutuhan aplikasi bengkel Anda.</h2>
        <p>Lihat fitur yang relevan, minta demo, dan konsultasikan source code, custom workflow, branding, atau deployment yang Anda perlukan.</p>
        <x-pseo.whatsapp-cta page-intent="{{ $page->intent }}" :message="'Halo, saya tertarik dengan '.$page->h1.'. Boleh minta informasi, demo, dan penawaran?'" label="Chat WhatsApp sekarang" />
    </section>
</div>
@endsection
