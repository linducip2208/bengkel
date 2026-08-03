{{--
    Usage:
    @include('partials.date-filter', [
        'startName' => 'start_date',
        'endName' => 'end_date',
        'startValue' => request('start_date'),
        'endValue' => request('end_date'),
    ])
--}}

@php
    $startName = $startName ?? 'start_date';
    $endName = $endName ?? 'end_date';
    $startValue = $startValue ?? request($startName, '');
    $endValue = $endValue ?? request($endName, '');
    $label = $label ?? 'Filter Tanggal';
@endphp

<form method="GET" class="row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small fw-semibold text-muted mb-1">{{ $label }}</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
            <input type="date" name="{{ $startName }}" value="{{ $startValue }}" class="form-control" style="max-width:150px;">
            <span class="input-group-text">s/d</span>
            <input type="date" name="{{ $endName }}" value="{{ $endValue }}" class="form-control" style="max-width:150px;">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            @if($startValue || $endValue)
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </div>
    </div>
</form>
