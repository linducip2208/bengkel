{{--
    Usage:
    @include('partials.rupiah', ['amount' => 150000])
    @include('partials.rupiah', ['amount' => 150000, 'prefix' => false])
    Output: format mengikuti default Currency di /currencies (mis. Rp 150.000, $ 150.00)

    Alternatif lebih ringkas: @money($amount)
--}}

@php
    $showPrefix = $prefix ?? true;
    if ($showPrefix) {
        echo \App\Models\Currency::format($amount ?? 0);
    } else {
        $default = \App\Models\Currency::default();
        $decimals = $default->code === 'IDR' ? 0 : 2;
        echo number_format((float) ($amount ?? 0), $decimals, ',', '.');
    }
@endphp
