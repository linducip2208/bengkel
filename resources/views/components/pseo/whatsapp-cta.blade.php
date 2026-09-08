@props([
    'pageIntent' => 'commercial',
    'message' => 'Halo, saya tertarik dengan Aplikasi Bengkel Profesional. Boleh minta informasi dan demo?',
    'label' => 'Konsultasi via WhatsApp',
    'class' => '',
])

@php
    $whatsapp = (string) config('product.whatsapp', '6281296052010');
    $link = 'https://wa.me/'.$whatsapp.'?text='.urlencode($message);
@endphp

<a href="{{ $link }}" target="_blank" rel="noopener" class="pseo-wa {{ $class }}"
   data-cta="whatsapp" data-source="pseo" data-page-intent="{{ $pageIntent }}">
    <span class="pseo-wa-icon" aria-hidden="true">↗</span>
    {{ $label }}
</a>
