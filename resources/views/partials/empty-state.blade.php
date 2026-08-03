{{--
    Usage:
    @include('partials.empty-state', [
        'icon' => 'fa-box-open',
        'title' => 'Belum ada produk',
        'message' => 'Tambahkan produk pertama Anda.',
        'actionUrl' => route('products.create'),
        'actionLabel' => 'Tambah Produk'
    ])
--}}

@php
    $emptyIcon = $icon ?? 'fa-inbox';
    $emptyTitle = $title ?? 'Tidak ada data';
    $emptyMessage = $message ?? 'Belum ada data yang tersedia.';
    $emptyActionUrl = $actionUrl ?? null;
    $emptyActionLabel = $actionLabel ?? 'Tambah Data';
@endphp

<div class="text-center py-5">
    <div class="mb-3">
        <i class="fas {{ $emptyIcon }} fa-4x text-muted opacity-25"></i>
    </div>
    <h5 class="text-muted fw-normal">{{ $emptyTitle }}</h5>
    <p class="text-muted small">{{ $emptyMessage }}</p>
    @if($emptyActionUrl)
        <a href="{{ $emptyActionUrl }}" class="btn btn-primary mt-2">
            <i class="fas fa-plus me-1"></i> {{ $emptyActionLabel }}
        </a>
    @endif
</div>
