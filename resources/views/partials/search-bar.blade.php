{{--
    Usage:
    @include('partials.search-bar', ['placeholder' => 'Cari pelanggan...'])
    @include('partials.search-bar', ['name' => 'q', 'placeholder' => 'Cari...'])
--}}

@php
    $searchName = $name ?? 'search';
    $searchPlaceholder = $placeholder ?? 'Cari...';
    $searchValue = request($searchName, '');
@endphp

<form method="GET" class="input-group input-group-sm" style="max-width: 280px;">
    <input type="text" name="{{ $searchName }}" value="{{ $searchValue }}" class="form-control" placeholder="{{ $searchPlaceholder }}">
    <button class="btn btn-outline-secondary" type="submit">
        <i class="fas fa-search"></i>
    </button>
    @if($searchValue)
        <a href="{{ url()->current() }}" class="btn btn-outline-danger" title="Reset">
            <i class="fas fa-times"></i>
        </a>
    @endif
</form>
