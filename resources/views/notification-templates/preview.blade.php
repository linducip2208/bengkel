@extends('layouts.app')

@section('title', 'Preview Template - ' . $notificationTemplate->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Preview: {{ $notificationTemplate->name }}</h4>
    <a href="{{ route('notification-templates.index') }}" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <div class="card-header">
        <strong>Channel:</strong> {{ ucfirst($notificationTemplate->channel) }} | <strong>Slug:</strong> <code>{{ $notificationTemplate->slug }}</code>
    </div>
    <div class="card-body">
        @if($subject)
        <div class="mb-3">
            <label class="form-label fw-bold">Subject:</label>
            <div class="p-2 bg-light rounded">{{ $subject }}</div>
        </div>
        @endif
        <div>
            <label class="form-label fw-bold">Body:</label>
            <div class="p-3 bg-light rounded" style="white-space: pre-wrap;">{!! nl2br(e($body)) !!}</div>
        </div>
    </div>
</div>

<div class="card mt-3 bg-light">
    <div class="card-body">
        <h6>Variables Used in Preview:</h6>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <tr><td><code>{customer_name}</code></td><td>Ahmad Fauzi</td></tr>
            <tr><td><code>{vehicle_plate}</code></td><td>B 1234 XYZ</td></tr>
            <tr><td><code>{service_date}</code></td><td>{{ now()->format('d/m/Y') }}</td></tr>
            <tr><td><code>{job_no}</code></td><td>JOB-2026-0001</td></tr>
            <tr><td><code>{invoice_number}</code></td><td>INV-2026-0001</td></tr>
            <tr><td><code>{total_amount}</code></td><td>Rp 1.500.000</td></tr>
            <tr><td><code>{payment_method}</code></td><td>Transfer Bank</td></tr>
            <tr><td><code>{workshop_name}</code></td><td>{{ config('app.name') }}</td></tr>
            <tr><td><code>{workshop_phone}</code></td><td>0812-3456-7890</td></tr>
        </table>
        </div>
    </div>
</div>
@endsection
