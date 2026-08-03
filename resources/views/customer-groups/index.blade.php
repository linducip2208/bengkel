@extends('layouts.app')
@section('title', 'Customer Groups')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-layer-group me-2"></i>Customer Groups (Fleet)</h4>
    <a href="{{ route('customer-groups.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah Group</a>
</div>
<div class="row">
    @forelse($groups as $g)
    <div class="col-md-4 mb-3">
        <div class="card"><div class="card-body">
            <h5><a href="{{ route('customer-groups.show', $g) }}">{{ $g->name }}</a> <span class="badge bg-info">{{ $g->customers_count }} customer</span></h5>
            <small class="text-muted">{{ $g->description }}</small>
            <div class="mt-2"><a href="{{ route('customer-groups.edit', $g) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a></div>
        </div></div>
    </div>
    @empty
    <div class="col-12"><p class="text-muted">Belum ada group.</p></div>
    @endforelse
</div>
@endsection
