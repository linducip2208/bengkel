{{--
    Usage:
    @include('partials.status-badge', ['type' => 'service', 'status' => 'Open'])
    @include('partials.status-badge', ['type' => 'invoice', 'status' => 'Unpaid'])
    @include('partials.status-badge', ['type' => 'purchase', 'status' => 'Draft'])
    @include('partials.status-badge', ['type' => 'generic', 'status' => 'Active', 'color' => 'success'])

    Supported types: service, invoice, purchase, generic
--}}

@php
    $badgeClass = 'secondary';
    $label = $status ?? 'Unknown';

    if ($type === 'service') {
        $map = [
            'open' => 'primary',
            'in progress' => 'warning',
            'progress' => 'warning',
            'done' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'canceled' => 'danger',
        ];
        $badgeClass = $map[strtolower($label)] ?? 'secondary';
    } elseif ($type === 'invoice') {
        $map = [
            'unpaid' => 'danger',
            'half paid' => 'warning',
            'full paid' => 'success',
            'paid' => 'success',
            'partial' => 'warning',
            'overdue' => 'dark',
            'cancelled' => 'secondary',
        ];
        $badgeClass = $map[strtolower($label)] ?? 'secondary';
    } elseif ($type === 'purchase') {
        $map = [
            'draft' => 'secondary',
            'ordered' => 'primary',
            'received' => 'success',
            'cancelled' => 'danger',
        ];
        $badgeClass = $map[strtolower($label)] ?? 'secondary';
    } elseif ($type === 'generic') {
        $badgeClass = $color ?? 'secondary';
    }
@endphp

<span class="badge bg-{{ $badgeClass }} bg-opacity-10 text-{{ $badgeClass }} rounded-pill px-3 py-1">
    {{ $label }}
</span>
