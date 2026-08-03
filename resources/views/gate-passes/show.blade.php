@extends('layouts.app')

@section('title', 'Gate Pass - ' . $gatePass->gate_pass_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Gate Pass: {{ $gatePass->gate_pass_no }}</h4>
    <div>
        <a href="{{ route('gate-passes.print', $gatePass) }}" class="btn btn-secondary"><i class="bi bi-printer"></i> Print</a>
        @if($gatePass->status === 'in')
        <form action="{{ route('gate-passes.mark-exit', $gatePass) }}" method="POST" class="d-inline" onsubmit="return confirm('Mark this vehicle as exited?')">
            @csrf @method('PUT')
            <button class="btn btn-success"><i class="bi bi-box-arrow-right"></i> Mark Exit</button>
        </form>
        @endif
        <a href="{{ route('gate-passes.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Vehicle & Customer</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th style="width:150px">License Plate</th><td>{{ $gatePass->vehicle->number_plate ?? '-' }}</td></tr>
                    <tr><th>Model</th><td>{{ $gatePass->vehicle->model_name ?? '-' }} ({{ $gatePass->vehicle->model_year ?? '-' }})</td></tr>
                    <tr><th>Customer</th><td>{{ $gatePass->vehicle->customer->name ?? $gatePass->customer->name ?? '-' }}</td></tr>
                    <tr><th>Customer Phone</th><td>{{ $gatePass->vehicle->customer->phone ?? $gatePass->customer->phone ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Gate Pass Details</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th style="width:150px">Status</th><td>
                        @if($gatePass->status === 'in')
                            <span class="badge bg-warning">In</span>
                        @else
                            <span class="badge bg-success">Out</span>
                        @endif
                    </td></tr>
                    <tr><th>Entry</th><td>{{ $gatePass->entry_date->format('d/m/Y H:i') }}</td></tr>
                    <tr><th>Exit</th><td>{{ $gatePass->exit_date ? $gatePass->exit_date->format('d/m/Y H:i') : '-' }}</td></tr>
                    <tr><th>Service</th><td>{{ $gatePass->service->job_no ?? 'No Service' }}</td></tr>
                    <tr><th>Driver</th><td>{{ $gatePass->driver_name ?: '-' }}</td></tr>
                    <tr><th>Driver Phone</th><td>{{ $gatePass->driver_phone ?: '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    @if($gatePass->notes)
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header"><strong>Notes</strong></div>
            <div class="card-body"><p class="mb-0">{{ $gatePass->notes }}</p></div>
        </div>
    </div>
    @endif
</div>
@endsection
