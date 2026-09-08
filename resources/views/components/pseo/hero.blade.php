@php
    $price = (int) config('product.starting_price', 7000000);
    $priceLabel = 'Rp '.number_format($price, 0, ',', '.');
    $message = 'Halo, saya tertarik dengan '.$page->h1.'. Boleh minta informasi dan demo?';
@endphp

<section class="pseo-hero">
    <div class="pseo-hero-copy">
        <div class="pseo-eyebrow">{{ $page->eyebrow }}</div>
        <h1>{{ $page->h1 }}</h1>
        <p class="pseo-lead">{{ $page->intro }}</p>
        <div class="pseo-hero-actions">
            <x-pseo.whatsapp-cta page-intent="{{ $page->intent }}" :message="$message" label="Tanya via WhatsApp" />
            <a class="pseo-btn pseo-btn-ghost" href="#fitur">Lihat fitur</a>
        </div>
        <div class="pseo-trust-row" aria-label="Keunggulan produk">
            @foreach(config('product.commercial_points', []) as $point)
                <span><b aria-hidden="true">✓</b> {{ $point }}</span>
            @endforeach
        </div>
    </div>
    <div class="pseo-hero-card">
        <div class="pseo-browser-bar"><span></span><span></span><span></span><small>{{ parse_url(url('/'), PHP_URL_HOST) }}/{{ $page->slug }}</small></div>
        <img src="{{ asset('marketing/screens/dashboard.png') }}" alt="Tampilan dashboard aplikasi bengkel" width="900" height="560" fetchpriority="high">
        <div class="pseo-price-float">
            <small>HARGA MULAI</small>
            <strong>{{ $priceLabel }}</strong>
            <span>Harga akhir menyesuaikan kebutuhan dan customisasi.</span>
        </div>
    </div>
</section>
