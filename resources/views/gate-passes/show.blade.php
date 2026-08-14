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
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tr><th style="width:150px">License Plate</th><td>{{ $gatePass->vehicle->number_plate ?? '-' }}</td></tr>
                    <tr><th>Model</th><td>{{ $gatePass->vehicle->model_name ?? '-' }} ({{ $gatePass->vehicle->model_year ?? '-' }})</td></tr>
                    <tr><th>Customer</th><td>{{ $gatePass->vehicle->customer->name ?? $gatePass->customer->name ?? '-' }}</td></tr>
                    <tr><th>Customer Phone</th><td>{{ $gatePass->vehicle->customer->phone ?? $gatePass->customer->phone ?? '-' }}</td></tr>
                </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><strong>Gate Pass Details</strong></div>
            <div class="card-body">
                <div class="table-responsive">
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
    </div>
    @if($gatePass->notes)
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header"><strong>Notes</strong></div>
            <div class="card-body"><p class="mb-0">{{ $gatePass->notes }}</p></div>
        </div>
    </div>
    @endif

    {{-- Digital Signature --}}
    <div class="col-md-6">
        <div class="card mb-3"><div class="card-header"><strong>Tanda Tangan Customer</strong></div><div class="card-body text-center">
            @if($gatePass->customer_signature)
                <img src="{{ $gatePass->customer_signature }}" style="max-width:300px;border:1px solid #ddd;">
            @else
                <canvas id="sigCanvas" width="350" height="150" style="border:1px solid #ccc;border-radius:8px;touch-action:none;"></canvas>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearSig()"><i class="fas fa-eraser"></i> Hapus</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="saveSig('customer')"><i class="fas fa-save"></i> Simpan</button>
                </div>
            @endif
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3"><div class="card-header"><strong>Tanda Tangan Teknisi</strong></div><div class="card-body text-center">
            @if($gatePass->technician_signature)
                <img src="{{ $gatePass->technician_signature }}" style="max-width:300px;border:1px solid #ddd;">
            @else
                <canvas id="techCanvas" width="350" height="150" style="border:1px solid #ccc;border-radius:8px;touch-action:none;"></canvas>
                <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearTechSig()"><i class="fas fa-eraser"></i> Hapus</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="saveSig('technician')"><i class="fas fa-save"></i> Simpan</button>
                </div>
            @endif
        </div></div>
    </div>
</div>
@push('scripts')
<script>
function initSig(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d'); let drawing = false;
    canvas.addEventListener('mousedown', e => { drawing = true; ctx.beginPath(); ctx.moveTo(e.offsetX, e.offsetY); });
    canvas.addEventListener('mousemove', e => { if(drawing){ ctx.lineTo(e.offsetX, e.offsetY); ctx.strokeStyle='#000'; ctx.lineWidth=2; ctx.stroke(); }});
    canvas.addEventListener('mouseup', () => drawing = false);
    canvas.addEventListener('touchstart', e => { e.preventDefault(); const t=e.touches[0]; const r=canvas.getBoundingClientRect(); drawing=true; ctx.beginPath(); ctx.moveTo(t.clientX-r.left, t.clientY-r.top); });
    canvas.addEventListener('touchmove', e => { e.preventDefault(); if(drawing){ const t=e.touches[0]; const r=canvas.getBoundingClientRect(); ctx.lineTo(t.clientX-r.left, t.clientY-r.top); ctx.strokeStyle='#000'; ctx.lineWidth=2; ctx.stroke(); }});
    canvas.addEventListener('touchend', () => drawing = false);
}
function clearSig() { const c=document.getElementById('sigCanvas'); c.getContext('2d').clearRect(0,0,c.width,c.height); }
function clearTechSig() { const c=document.getElementById('techCanvas'); c.getContext('2d').clearRect(0,0,c.width,c.height); }
function saveSig(type) {
    const id = type === 'customer' ? 'sigCanvas' : 'techCanvas';
    const data = document.getElementById(id).toDataURL();
    fetch('{{ route('gate-passes.update', $gatePass) }}', {
        method: 'PUT', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({[type+'_signature']: data, _method: 'PUT'})
    }).then(r => { if(r.ok) location.reload(); });
}
initSig('sigCanvas'); initSig('techCanvas');
</script>
@endpush
@endif
