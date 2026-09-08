<section class="pseo-split-section">
    <div>
        <div class="pseo-eyebrow">KENAPA SOURCE CODE?</div>
        <h2>Mulai dari fondasi yang bisa dikembangkan</h2>
        <p>Source code memberi ruang untuk membahas kebutuhan deployment, custom workflow, branding, dan integrasi dengan lebih konkret. Detail penggunaan diberikan sesuai paket dan lisensi yang disepakati.</p>
        <x-pseo.whatsapp-cta page-intent="{{ $page->intent }}" message="Halo, saya ingin membahas source code Aplikasi Bengkel Profesional dan kebutuhan custom saya." label="Bahas kebutuhan saya" />
    </div>
    <div class="pseo-benefit-list">
        @foreach(['Sistem dapat dikembangkan mengikuti workflow', 'Dapat dibahas untuk server dan domain sendiri', 'Lebih fleksibel untuk integrasi lanjutan', 'Branding dan white-label dapat dikonsultasikan'] as $benefit)
            <div><span>✓</span><strong>{{ $benefit }}</strong></div>
        @endforeach
    </div>
</section>
