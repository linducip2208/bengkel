@extends('pseo._layout')
@section('content')
<section class="content">
    <h2>{{ $context ?? ucwords(str_replace('-', ' ', $slug)) }}</h2>

    <p>Aplikasi Bengkel Terbaik adalah bengkel mobil profesional yang melayani berbagai kebutuhan perawatan dan perbaikan kendaraan di Indonesia.@if($cityName) Kami hadir di area <strong>{{ $cityName }}</strong> dan sekitarnya.@endif Dengan teknisi berpengalaman dan peralatan modern, kami siap memberikan layanan terbaik untuk kendaraan Anda.</p>

    @if($serviceName || $brandName || $cityName)
    <h3>Layanan Kami</h3>
    <ul>
        @if($serviceName)<li><strong>{{ $serviceName }}</strong> — layanan profesional dengan garansi</li>@endif
        @if($brandName)<li>Spesialis mobil <strong>{{ $brandName }}</strong></li>@endif
        @if($cityName)<li>Melayani area <strong>{{ $cityName }}</strong> dan sekitarnya</li>@endif
        @if($priceLabel)<li>Harga mulai <strong>{{ $priceLabel }}</strong></li>@endif
        @if(($yearLabel ?? 0) > 2000)<li>Tersedia tahun <strong>{{ $yearLabel }}</strong></li>@endif
    </ul>
    @endif

    <h3>Mengapa Memilih Aplikasi Bengkel Terbaik?</h3>
    <p>Kami mengutamakan kualitas, transparansi harga, dan kepuasan pelanggan. Setiap pekerjaan dikerjakan oleh teknisi bersertifikat menggunakan sparepart genuine atau OEM berkualitas. Kami memberikan garansi untuk setiap service yang kami lakukan.</p>
    <p>Dengan sistem manajemen bengkel modern, kami mencatat setiap riwayat service kendaraan Anda sehingga perawatan menjadi lebih terencana dan efisien. Booking online tersedia 24 jam melalui website kami.</p>
</section>

@if($isSourceCode)
<div class="sc-cta">
    <h3>Beli Source Code Aplikasi Bengkel</h3>
    <p>Aplikasi bengkel full source code Laravel — siap pakai, bisa custom, gratis demo. Dapatkan sekarang!</p>
    <a href="https://wa.me/6281234567890?text=Halo%2C%20saya%20tertarik%20source%20code%20aplikasi%20bengkel" class="btn">Chat WhatsApp Sekarang</a>
    <p style="font-size:0.85rem;margin-top:0.75rem">Harga terjangkau &middot; Source code lengkap &middot; Bisa request custom fitur</p>
</div>
@endif

<section class="content">
    <h3>Frequently Asked Questions</h3>
    <div class="faq-item">
        <strong>Apa layanan unggulan Aplikasi Bengkel Terbaik?</strong>
        <p>Kami melayani {{ $serviceName ?? 'semua jenis perawatan dan perbaikan mobil' }}, dari servis berkala, ganti oli, tune-up, body repair, hingga overhaul mesin. Semua dikerjakan teknisi profesional.</p>
    </div>
    <div class="faq-item">
        <strong>@if($cityName)Apakah melayani area {{ $cityName }}?@else Apakah bisa booking online?@endif</strong>
        <p>@if($cityName)Ya, Aplikasi Bengkel Terbaik melayani area {{ $cityName }} dan sekitarnya. @endifBooking online tersedia 24 jam. Hubungi WhatsApp kami atau kunjungi bengkel langsung untuk konsultasi gratis.</p>
    </div>
    <div class="faq-item">
        <strong>Berapa biaya {{ $serviceName ?? 'service' }}?</strong>
        <p>Biaya bervariasi tergantung jenis kendaraan dan tingkat kerusakan.@if($priceLabel) Harga mulai dari <strong>{{ $priceLabel }}</strong>.@else Kami memberikan estimasi transparan sebelum pekerjaan dimulai, tanpa biaya tersembunyi.@endif</p>
    </div>
    @if($isSourceCode)
    <div class="faq-item">
        <strong>Bagaimana cara beli source code aplikasi bengkel?</strong>
        <p>Hubungi WhatsApp kami untuk demo dan pricing. Source code lengkap Laravel + MySQL, bisa di-custom sesuai kebutuhan. Harga terjangkau, cocok untuk startup bengkel atau pengembang software.</p>
    </div>
    @endif
</section>

<section class="content">
    <h3>Layanan Terkait</h3>
    <ul>
        @foreach($relatedServices as $svc)
        <li><a href="{{ url('/' . $svc['slug'] . ($cityName ? '-' . $city : '')) }}">{{ $svc['name'] }}</a></li>
        @endforeach
    </ul>
</section>
@endsection
