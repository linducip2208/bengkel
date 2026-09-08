<section class="pseo-section pseo-faq-section" id="faq">
    <div class="pseo-section-heading"><div class="pseo-eyebrow">FAQ</div><h2>Pertanyaan sebelum memilih aplikasi</h2></div>
    <div class="pseo-faq-grid">
        @foreach($page->faq as $item)
            <details class="pseo-faq-item" @if($loop->first) open @endif>
                <summary>{{ $item['question'] }} <span aria-hidden="true">+</span></summary>
                <p>{{ $item['answer'] }}</p>
            </details>
        @endforeach
    </div>
</section>
