@extends('layouts.app')

@section('title')
Reminders - {{ config('app.name') }}
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Reminders</h4>
    <div>
        @can('reminder.send')
        <a href="{{ route('reminders.send-scheduled') }}" class="btn btn-warning"
           onclick="event.preventDefault(); document.getElementById('send-scheduled-form').submit();">
            <i class="bi bi-send"></i> Send Due Reminders
        </a>
        <form id="send-scheduled-form" action="{{ route('reminders.send-scheduled') }}" method="POST" class="d-none">
            @csrf
        </form>
        @endcan
        @can('reminder.create')
        <a href="{{ route('reminders.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Reminder</a>
        @endcan
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>Service</option>
                    <option value="insurance" {{ request('type') === 'insurance' ? 'selected' : '' }}>Insurance</option>
                    <option value="stnk" {{ request('type') === 'stnk' ? 'selected' : '' }}>STNK</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="{{ route('reminders.index') }}" class="btn btn-outline-danger">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Type</th>
                    <th>Reminder Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reminders as $reminder)
                <tr>
                    <td>{{ $reminder->customer->name ?? '-' }}</td>
                    <td>{{ $reminder->vehicle->number_plate ?? '-' }}</td>
                    <td>
                        @if($reminder->type === 'service')<span class="badge bg-primary">Service</span>
                        @elseif($reminder->type === 'insurance')<span class="badge bg-info">Insurance</span>
                        @else<span class="badge bg-warning">STNK</span>@endif
                    </td>
                    <td>{{ $reminder->reminder_date->format('d/m/Y') }}</td>
                    <td>
                        @if($reminder->is_sent)
                            <span class="badge bg-success">Sent {{ $reminder->sent_at?->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="badge bg-secondary">Pending</span>
                        @endif
                    </td>
                    <td>
                        @unless($reminder->is_sent)
                        @can('reminder.send')
                        <form action="{{ route('reminders.send', $reminder) }}" method="POST" class="d-inline" onsubmit="return confirm('Send this reminder now?')">
                            @csrf
                            <button class="btn btn-sm btn-success"><i class="bi bi-send"></i> Send</button>
                        </form>
                        @endcan
                        @endunless
                        @can('reminder.edit')
                        <a href="{{ route('reminders.edit', $reminder) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        @endcan
                        @can('reminder.delete')
                        <form action="{{ route('reminders.destroy', $reminder) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this reminder?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center">No reminders found.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
<div class="mt-2">{{ $reminders->links() }}</div>
@endsection
