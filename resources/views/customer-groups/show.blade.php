@extends('layouts.app')
@section('title', $customerGroup->name)
@section('content')
<h4><i class="fas fa-layer-group me-2"></i>{{ $customerGroup->name }}</h4>
<p class="text-muted">{{ $customerGroup->description }}</p>
<div class="card mt-3"><div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0"><thead><tr><th>Nama</th><th>Telepon</th><th>Kendaraan</th></tr></thead><tbody>
    @forelse($customers as $c)
    <tr>
        <td><a href="{{ route('customers.show', $c) }}"><strong>{{ $c->name }}</strong></a></td>
        <td>{{ $c->phone }}</td>
        <td>{{ $c->vehicles->count() }} kendaraan</td>
    </tr>
    @empty
    <tr><td colspan="3" class="text-center py-3 text-muted">Belum ada customer di group ini.</td></tr>
    @endforelse
</tbody></table></div></div></div>
{{ $customers->links() }}
@endsection
