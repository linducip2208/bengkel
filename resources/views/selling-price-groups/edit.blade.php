@extends('layouts.app')
@section('title', 'Edit Selling Price Group')
@section('content')
<div class="card">
    <div class="card-header"><h6 class="card-title mb-0">Edit Selling Price Group</h6></div>
    <div class="card-body">
        <form method="POST" action="{{ route('selling-price-groups.update', $sellingPriceGroup) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Nama Group *</label>
                    <input type="text" name="name" class="form-control" value="{{ $sellingPriceGroup->name }}" required>
                </div>
                <div class="col-md-6">
                    <label>Deskripsi</label>
                    <input type="text" name="description" class="form-control" value="{{ $sellingPriceGroup->description }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" {{ $sellingPriceGroup->is_active ? 'checked' : '' }}>
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
