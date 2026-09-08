<section class="pseo-section pseo-workflow-section">
    <div class="pseo-section-heading">
        <div class="pseo-eyebrow">ALUR OPERASIONAL</div>
        <h2>Customer masuk sampai selesai dalam satu alur</h2>
        <p>Struktur internal aplikasi menjaga setiap bagian bekerja dengan konteks yang sama.</p>
    </div>
    <div class="pseo-workflow">
        @foreach(['Customer masuk', 'Check-in', 'Temuan', 'Estimasi', 'Pekerjaan', 'QC', 'Invoice', 'Selesai'] as $step)
            <div class="pseo-workflow-step"><b>{{ $loop->iteration }}</b><span>{{ $step }}</span></div>
            @if(!$loop->last)<i aria-hidden="true">→</i>@endif
        @endforeach
    </div>
</section>
