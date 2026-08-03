@extends('layouts.app')

@section('title', 'Detail Reminder')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Reminder</h4>
    <div>
        @unless($reminder->is_sent)
        <a href="{{ route('reminders.edit', $reminder) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <form action="{{ route('reminders.send', $reminder) }}" method="POST" class="d-inline" onsubmit="return confirm('Send this reminder now?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">
                <i class="bi bi-send"></i> Send
            </button>
        </form>
        @endunless
        <a href="{{ route('reminders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Informasi Reminder</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="150" class="text-muted">Type</td>
                        <td>
                            @if($reminder->type === 'service')<span class="badge bg-primary">Service</span>
                            @elseif($reminder->type === 'insurance')<span class="badge bg-info">Insurance</span>
                            @else<span class="badge bg-warning">STNK</span>@endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Reminder Date</td>
                        <td>{{ $reminder->reminder_date->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            @if($reminder->is_sent)
                                <span class="badge bg-success">Sent {{ $reminder->sent_at?->format('d/m/Y H:i') }}</span>
                            @else
                                <span class="badge bg-secondary">Pending</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Customer & Vehicle</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="150" class="text-muted">Customer</td>
                        <td>{{ $reminder->customer->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phone</td>
                        <td>{{ $reminder->customer->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Vehicle</td>
                        <td>{{ $reminder->vehicle->number_plate ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Model</td>
                        <td>{{ $reminder->vehicle->model_name ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

@if($reminder->message)
<div class="card mb-3">
    <div class="card-header"><strong>Pesan</strong></div>
    <div class="card-body">
        <p class="mb-0">{{ $reminder->message }}</p>
    </div>
</div>
@endif

<div class="mt-3">
    <a href="{{ route('reminders.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@endsection
