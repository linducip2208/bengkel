@extends('layouts.app')
@section('title', 'Transfer Stok Baru')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('warehouses.transfers.store') }}">@csrf
<div class="row g-3 mb-3">
    <div class="col-md-5"><label>Dari Gudang *</label><select name="from_warehouse_id" class="form-select" required><option value="">-- Pilih --</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>@endforeach</select></div>
    <div class="col-md-5"><label>Ke Gudang *</label><select name="to_warehouse_id" class="form-select" required><option value="">-- Pilih --</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>@endforeach</select></div>
</div>
<table class="table table-bordered" id="transferItems"><thead><tr><th>Produk *</th><th class="text-end" width="150">Qty *</th><th></th></tr></thead>
<tbody><tr>
    <td><select name="items[0][product_id]" class="form-select" required><option value="">-- Pilih Produk --</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>@endforeach</select></td>
    <td><input type="number" name="items[0][quantity]" class="form-control" value="1" min="1" required></td>
    <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>
</tr></tbody></table>
<button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addTransferItem()"><i class="fas fa-plus"></i> Tambah Produk</button>
<button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Transfer Stok</button>
</form></div></div>
@push('scripts')
<script>
let ti = 1;
function addTransferItem(){
    const tbody = document.querySelector('#transferItems tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="items[${ti}][product_id]" class="form-select" required><option value="">-- Pilih --</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>@endforeach</select></td>
        <td><input type="number" name="items[${ti}][quantity]" class="form-control" value="1" min="1" required></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="fas fa-trash"></i></button></td>`;
    tbody.appendChild(tr); ti++;
}
</script>
@endpush
@endsection
