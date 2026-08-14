@extends('layouts.app')
@section('title', $warehouse->name)
@section('content')
<h4><i class="fas fa-warehouse me-2"></i>{{ $warehouse->name }} <small class="text-muted">{{ $warehouse->code }}</small></h4>
<div class="card mt-3"><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Produk</th><th class="text-end">Stok</th><th>Rak</th></tr></thead><tbody>
    @forelse($stocks as $s)
    <tr><td>{{ $s->product->name ?? '#' . $s->product_id }}</td><td class="text-end">{{ $s->quantity }}</td><td>{{ $s->rack_location ?? '-' }}</td></tr>
    @empty
    <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada stok di gudang ini.</td></tr>
    @endforelse
</tbody></table></div></div></div>
{{ $stocks->links() }}
@endsection
