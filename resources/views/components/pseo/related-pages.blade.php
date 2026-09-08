@if(count($page->relatedLinks) > 0)
    <section class="pseo-section pseo-related-section">
        <div class="pseo-section-heading"><div class="pseo-eyebrow">LANJUTKAN EKSPLORASI</div><h2>Halaman terkait</h2></div>
        <div class="pseo-related-grid">
            @foreach($page->relatedLinks as $link)
                <a href="{{ url('/'.$link['slug']) }}" class="pseo-related-card">{{ $link['label'] }} <span aria-hidden="true">→</span></a>
            @endforeach
        </div>
    </section>
@endif
