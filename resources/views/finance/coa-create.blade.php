@extends('layouts.app')
@section('title', 'Tambah Akun')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('finance.coa.store') }}">@csrf
<div class="row g-3">
    <div class="col-md-3"><label>Kode *</label><input type="text" name="code" class="form-control" required></div>
    <div class="col-md-5"><label>Nama Akun *</label><input type="text" name="name" class="form-control" required></div>
    <div class="col-md-2"><label>Tipe *</label><select name="type" class="form-select" required><option value="asset">Aset</option><option value="liability">Liabilitas</option><option value="equity">Ekuitas</option><option value="income">Pendapatan</option><option value="expense">Beban</option></select></div>
    <div class="col-md-2"><label>Parent</label><select name="parent_id" class="form-select"><option value="">-- None --</option>@foreach($parents as $p)<option value="{{ $p->id }}">{{ $p->code }} - {{ $p->name }}</option>@endforeach</select></div>
    <div class="col-12"><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
</div>
</form></div></div>
@endsection
