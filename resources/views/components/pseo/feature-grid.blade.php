<section id="fitur" class="pseo-section">
    <div class="pseo-section-heading">
        <div class="pseo-eyebrow">FITUR YANG SUDAH TERSEDIA</div>
        <h2>Alur kerja bengkel yang terlihat jelas</h2>
        <p>Setiap modul dijelaskan dari manfaatnya untuk operasional, bukan hanya dari nama menu.</p>
    </div>
    <div class="pseo-feature-grid">
        @foreach($page->features as $index => $feature)
            <article class="pseo-feature-card" style="--delay: {{ $index * 60 }}ms">
                <span class="pseo-feature-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <h3>{{ $feature['name'] }}</h3>
                <p>{{ $feature['description'] }}</p>
            </article>
        @endforeach
    </div>
</section>
