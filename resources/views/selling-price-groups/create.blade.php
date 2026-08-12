@extends('layouts.app')
@section('title', 'Tambah Selling Price Group')
@section('content')
<div class="card">
    <div class="card-header"><h6 class="card-title mb-0">Tambah Selling Price Group</h6></div>
    <div class="card-body">
        <form method="POST" action="{{ route('selling-price-groups.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Nama Group *</label>
                    <input type="text" name="name" class="form-control" placeholder="Grosir, Bengkel Rekanan, Corporate..." required>
                </div>
                <div class="col-md-6">
                    <label>Deskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Keterangan grup">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" checked>
                        <label class="form-check-label" for="isActive">Aktif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
                    <a href="{{ route('selling-price-groups.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
