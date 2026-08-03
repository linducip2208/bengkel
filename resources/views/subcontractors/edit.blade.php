@extends('layouts.app')
@section('title', 'Edit Subkontraktor')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Subkontraktor</h4>
    <a href="{{ route('subcontractors.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('subcontractors.update', $subcontractor) }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nama *</label><input type="text" name="name" class="form-control" value="{{ old('name', $subcontractor->name) }}" required></div>
            <div class="col-md-6"><label class="form-label">Spesialisasi</label><input type="text" name="specialty" class="form-control" value="{{ old('specialty', $subcontractor->specialty) }}"></div>
            <div class="col-md-6"><label class="form-label">Telepon</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $subcontractor->phone) }}"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $subcontractor->email) }}"></div>
            <div class="col-12"><label class="form-label">Alamat</label><textarea name="address" rows="2" class="form-control">{{ old('address', $subcontractor->address) }}</textarea></div>
            <div class="col-12"><label class="form-label">Catatan</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $subcontractor->notes) }}</textarea></div>
            <div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button></div>
        </div>
    </form>
</div></div>
@endsection
