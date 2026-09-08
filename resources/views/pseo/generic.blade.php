@extends('pseo._layout')

@section('content')
<div class="pseo-wrap">
    <nav class="pseo-breadcrumb" aria-label="Breadcrumb"><a href="{{ url('/') }}">Beranda</a><span>/</span><span>Informasi aplikasi bengkel</span></nav>
    <section class="pseo-content-legacy">
        <div class="pseo-eyebrow">INFORMASI APLIKASI BENGKEL</div>
        <h1>{{ $metaTitle ?? 'Aplikasi Bengkel Profesional' }}</h1>
        <p>{{ $metaDescription ?? 'Informasi aplikasi manajemen bengkel berbasis web.' }}</p>
        <p>Halaman ini merupakan halaman informasi lama. Untuk halaman komersial yang lebih lengkap, lihat <a href="{{ url('/source-code-aplikasi-bengkel') }}">Source Code Aplikasi Bengkel Profesional</a> dan <a href="{{ url('/harga-aplikasi-bengkel') }}">harga aplikasi bengkel</a>.</p>
        <x-pseo.whatsapp-cta page-intent="legacy" message="Halo, saya tertarik dengan Aplikasi Bengkel Profesional. Boleh minta informasi dan demo?" label="Konsultasi via WhatsApp" />
    </section>
</div>
@endsection
