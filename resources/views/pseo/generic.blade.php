@extends('pseo._layout')
@section('content')
<section class="content">
    <h2>{{ $context ?? ucwords(str_replace('-', ' ', $slug)) }}</h2>

    <p><strong>ERP Bengkel Indonesia</strong> (ERP Repair Car Indonesia) adalah aplikasi bengkel standard Indonesia berbasis web (web based) yang dirancang untuk mengelola seluruh operasional bengkel mobil & motor dalam satu sistem.@if($cityName) Tersedia untuk area <strong>{{ $cityName }}</strong> dan seluruh Indonesia.@endif Full source code Laravel + MySQL, harga mulai <strong>Rp 6.000.000</strong>.</p>

    @if($serviceName || $brandName || $cityName)
    <h3>Fitur Utama</h3>
    <ul>
        @if($serviceName)<li><strong>{{ $serviceName }}</strong> — modul lengkap, siap pakai</li>@endif
        @if($brandName)<li>Dukungan multi-cabang & multi-gudang untuk {{ $brandName }}</li>@endif
        @if($cityName)<li>Tersedia untuk <strong>{{ $cityName }}</strong> dan seluruh Indonesia</li>@endif
        @if($priceLabel)<li>Harga mulai <strong>{{ $priceLabel }}</strong></li>@endif
        <li><strong>Web Based</strong> — akses dari HP/PC/tablet, tanpa install</li>
    </ul>
    @endif

    <h3>Kenapa Pilih ERP Bengkel Indonesia?</h3>
    <p>ERP Bengkel Indonesia adalah aplikasi bengkel standard Indonesia paling lengkap: service/jobcard 13-status workflow, POS kasir, inventory multi-gudang, invoice & pembayaran, akuntansi otomatis, teknisi & komisi, warranty, CRM & loyalty, booking online, dan 13+ laporan.</p>
    <p>Dibangun dengan Laravel (PHP) + MySQL, <strong>web based</strong> sehingga bisa diakses dari mana saja. Full source code — Anda bebas modifikasi, rebrand, atau jual kembali (whitelabel).</p>

    <h3>Modul Lengkap</h3>
    <ul>
        <li>Dashboard & 13-status service workflow</li>
        <li>POS Kasir + sesi + hitung uang fisik</li>
        <li>Inventory multi-gudang + stock adjustment + transfer</li>
        <li>Invoice, payment, DP, cicil, retur</li>
        <li>Akuntansi otomatis (COA, journal, GL, P&L, balance sheet)</li>
        <li>Teknisi, komisi, skill matrix, cuti</li>
        <li>Warranty, klaim asuransi, klaim supplier, recall</li>
        <li>CRM: voucher, loyalty poin, blog, campaign</li>
        <li>Booking online + customer portal + API mobile</li>
    </ul>
</section>

@if($isSourceCode)
<div class="sc-cta">
    <h3>Beli Aplikasi Bengkel Standard Indonesia (ERP)</h3>
    <p>ERP Bengkel Indonesia — web based, full source code Laravel, harga mulai Rp 6.000.000. Siap pakai, bisa custom, gratis demo.</p>
    <a href="https://wa.me/6281296052010?text=Halo%2C%20saya%20tertarik%20ERP%20Bengkel%20Indonesia" class="btn">Chat WhatsApp 081296052010</a>
    <p style="font-size:0.85rem;margin-top:0.75rem">Harga mulai Rp 6.000.000 &middot; Full source code &middot; Web based &middot; Bisa whitelabel</p>
</div>
@else
<div class="sc-cta">
    <h3>Dapatkan ERP Bengkel Indonesia Sekarang</h3>
    <p>Aplikasi bengkel standard Indonesia, web based, full source code. Harga mulai Rp 6.000.000.</p>
    <a href="https://wa.me/6281296052010?text=Halo%2C%20saya%20tertarik%20ERP%20Bengkel%20Indonesia" class="btn">Chat WhatsApp 081296052010</a>
    <p style="font-size:0.85rem;margin-top:0.75rem">Web based &middot; Full source code &middot; Harga mulai Rp 6.000.000</p>
</div>
@endif

<section class="content">
    <h3>Frequently Asked Questions</h3>
    <div class="faq-item">
        <strong>Apa itu ERP Bengkel Indonesia?</strong>
        <p>ERP Bengkel Indonesia (ERP Repair Car Indonesia) adalah aplikasi bengkel standard Indonesia berbasis web untuk mengelola service, inventory, POS, invoice, akuntansi, teknisi, warranty, dan laporan dalam satu sistem. Full source code Laravel + MySQL.</p>
    </div>
    <div class="faq-item">
        <strong>Berapa harga aplikasi bengkel ini?</strong>
        <p>Harga mulai <strong>Rp 6.000.000</strong> untuk full source code, web based, siap pakai. Bisa request custom fitur. Hubungi WhatsApp 081296052010 untuk demo gratis.</p>
    </div>
    <div class="faq-item">
        <strong>Apakah web based?</strong>
        <p>Ya, ERP Bengkel Indonesia sepenuhnya <strong>web based</strong> — bisa diakses dari HP, PC, atau tablet melalui browser tanpa perlu install aplikasi.</p>
    </div>
    @if($isSourceCode)
    <div class="faq-item">
        <strong>Bagaimana cara beli source code?</strong>
        <p>Hubungi WhatsApp 081296052010 untuk demo & pricing. Source code lengkap Laravel + MySQL, bisa di-custom, di-rebrand, atau di-whitelabel sesuai kebutuhan bisnis Anda.</p>
    </div>
    @endif
</section>

<section class="content">
    <h3>Fitur Terkait</h3>
    <ul>
        @foreach($relatedServices as $svc)
        <li><a href="{{ url('/' . $svc['slug'] . ($cityName ? '-' . $city : '')) }}">{{ $svc['name'] }}</a></li>
        @endforeach
    </ul>
</section>
@endsection
