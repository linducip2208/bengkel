@extends('layouts.app')

@section('title', 'Cetak Estimasi: '.$estimate->estimate_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="fas fa-file-signature text-warning me-2"></i>{{ $estimate->estimate_number }} <span class="badge bg-secondary">v{{ $estimate->version }}</span></h5>
    <div>
        <a href="{{ route('estimates.pdf', $estimate) }}" class="btn btn-outline-danger btn-sm"><i class="fas fa-file-pdf me-1"></i> PDF</a>
        <a href="{{ route('services.show', $estimate->service_id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
    </div>
</div>

<div class="card"><div class="card-body">
    @include('estimates.form-reference', ['estimate' => $estimate, 'company' => $estimate->snapshotCompany(), 'customer' => $estimate->snapshotCustomer(), 'vehicle' => $estimate->snapshotVehicle(), 'service' => $estimate->snapshotService()])
</div></div>

@section('styles')
<style>
    @media print {
        .no-print, aside, nav, footer { display: none !important; }
        @page { size: A4; margin: 12mm; }
        body { background: #fff !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>
@endsection
