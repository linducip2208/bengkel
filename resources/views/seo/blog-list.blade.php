@extends('pseo._layout')
@section('content')
<section class="content" style="text-align:center">
    <h2>Blog {{ config('app.name') }}</h2>
    <p>Tips perawatan mobil, berita otomotif, dan panduan service kendaraan dari {{ config('app.name') }}.</p>
</section>

<section class="content">
    <h3>Artikel Terbaru</h3>
    <div style="display:grid;gap:1rem;margin-top:1rem">
        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:1.25rem">
            <h3 style="margin:0 0 0.3rem"><a href="{{ url('/blog/car-maintenance-tips') }}">Essential Car Maintenance Tips for Indonesian Roads</a></h3>
            <p style="color:#64748b;font-size:0.9rem;margin:0">15 Jan 2025 &middot; Tips perawatan mobil untuk kondisi jalan Indonesia.</p>
        </div>
        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:1.25rem">
            <h3 style="margin:0 0 0.3rem"><a href="{{ url('/blog/signs-your-car-needs-repair') }}">10 Signs Your Car Needs Immediate Repair</a></h3>
            <p style="color:#64748b;font-size:0.9rem;margin:0">20 Feb 2025 &middot; Kenali tanda-tanda mobil butuh perbaikan segera.</p>
        </div>
        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:1.25rem">
            <h3 style="margin:0 0 0.3rem"><a href="{{ url('/blog/choose-right-workshop') }}">How to Choose the Right Workshop for Your Vehicle</a></h3>
            <p style="color:#64748b;font-size:0.9rem;margin:0">10 Mar 2025 &middot; Panduan memilih bengkel yang tepat.</p>
        </div>
    </div>
</section>

<div class="sc-cta">
    <h3>Source Code Aplikasi Bengkel</h3>
    <p>Miliki aplikasi manajemen bengkel sendiri. Full source code Laravel — bisa custom fitur sesuai kebutuhan bisnis Anda.</p>
    <a href="https://wa.me/6281234567890" class="btn">Chat WhatsApp</a>
</div>
@endsection
