@extends('layouts.app')
@section('title', 'Edit Group')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('customer-groups.update', $customerGroup) }}">@csrf @method('PUT')
<div class="row g-3">
    <div class="col-md-6"><label>Nama Group *</label><input type="text" name="name" class="form-control" value="{{ $customerGroup->name }}" required></div>
    <div class="col-md-6">
        <label>Grup Harga Jual</label>
        <select name="selling_price_group_id" class="form-control">
            <option value="">— Tanpa Grup Harga —</option>
            @foreach($sellingPriceGroups as $g)
            <option value="{{ $g->id }}" {{ $customerGroup->selling_price_group_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12"><label>Deskripsi</label><textarea name="description" rows="2" class="form-control">{{ $customerGroup->description }}</textarea></div>
    <div class="col-12"><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
</div></form></div></div>
@endsection
