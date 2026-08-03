@extends('layouts.app')
@section('title', 'Tambah Gudang')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('warehouses.store') }}">@csrf
<div class="row g-3">
    <div class="col-md-6"><label>Nama *</label><input type="text" name="name" class="form-control" required></div>
    <div class="col-md-3"><label>Kode *</label><input type="text" name="code" class="form-control" required></div>
    <div class="col-md-3"><label>Cabang</label><select name="branch_id" class="form-select"><option value="">-- Semua --</option>@foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
    <div class="col-12"><label>Alamat</label><textarea name="address" rows="2" class="form-control"></textarea></div>
    <div class="col-12"><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
</div></form></div></div>
@endsection
