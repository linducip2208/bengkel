@extends('layouts.app')
@section('title', 'Tambah Group')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('customer-groups.store') }}">@csrf
<div class="row g-3">
    <div class="col-md-6"><label>Nama Group *</label><input type="text" name="name" class="form-control" placeholder="Hotel A, Fleet Taxi B, Perusahaan X..." required></div>
    <div class="col-12"><label>Deskripsi</label><textarea name="description" rows="2" class="form-control"></textarea></div>
    <div class="col-12"><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
</div></form></div></div>
@endsection
