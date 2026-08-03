@extends('layouts.app')
@section('title', 'Gudang')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-warehouse me-2"></i>Gudang</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('warehouses.transfers') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-exchange-alt me-1"></i>Transfer Stok</a>
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Gudang</a>
    </div>
</div>
<div class="row">
    @forelse($warehouses as $wh)
    <div class="col-md-4 mb-3">
        <div class="card"><div class="card-body">
            <h5><a href="{{ route('warehouses.show', $wh) }}">{{ $wh->name }}</a></h5>
            <small class="text-muted">Kode: {{ $wh->code }} | {{ $wh->branch->name ?? 'Semua Cabang' }}</small>
            <div class="mt-2"><a href="{{ route('warehouses.edit', $wh) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a></div>
        </div></div>
    </div>
    @empty
    <div class="col-12"><p class="text-muted">Belum ada gudang.</p></div>
    @endforelse
</div>
@endsection
