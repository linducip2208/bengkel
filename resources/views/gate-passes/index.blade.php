@extends('layouts.app')

@section('title', 'Gate Passes - {{ config('app.name') }}')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Gate Passes</h4>
    <a href="{{ route('gate-passes.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Gate Pass</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Gate Pass No</th>
                    <th>Date/Time In</th>
                    <th>Vehicle</th>
                    <th>Customer</th>
                    <th>Service</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gatePasses as $gp)
                <tr>
                    <td><strong>{{ $gp->gate_pass_no }}</strong></td>
                    <td>{{ $gp->entry_date->format('d/m/Y H:i') }}</td>
                    <td>{{ $gp->vehicle?->number_plate ?? '-' }} ({{ $gp->vehicle?->model_name ?? 'N/A' }})</td>
                    <td>{{ $gp->vehicle?->customer?->name ?? $gp->customer?->name ?? '-' }}</td>
                    <td>{{ $gp->service?->job_no ?? '-' }}</td>
                    <td>
                        @if($gp->status === 'in')
                            <span class="badge bg-warning">In</span>
                        @else
                            <span class="badge bg-success">Out</span>
                            <small class="d-block">{{ $gp->exit_date?->format('d/m/Y H:i') }}</small>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('gate-passes.show', $gp) }}" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('gate-passes.print', $gp) }}" class="btn btn-sm btn-secondary"><i class="bi bi-printer"></i></a>
                        @if($gp->status === 'in')
                        <form action="{{ route('gate-passes.mark-exit', $gp) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark vehicle as exited?')">
                            @csrf @method('PUT')
                            <button class="btn btn-sm btn-success"><i class="bi bi-box-arrow-right"></i> Exit</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center">No gate passes found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2">{{ $gatePasses->links() }}</div>
@endsection
