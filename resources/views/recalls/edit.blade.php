@extends('layouts.app')
@section('title', 'Edit Recall')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="fas fa-exclamation-triangle me-2"></i>Edit Recall: {{ $recall->title }}</h4>
    <a href="{{ route('recalls.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>
<div class="card"><div class="card-body">
<form action="{{ route('recalls.update', $recall) }}" method="POST">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Judul Recall <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                value="{{ old('title', $recall->title) }}" required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Issue Date <span class="text-danger">*</span></label>
            <input type="date" name="issue_date" class="form-control @error('issue_date') is-invalid @enderror"
                value="{{ old('issue_date', $recall->issue_date?->format('Y-m-d')) }}" required>
            @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Severity <span class="text-danger">*</span></label>
            <select name="severity" class="form-select @error('severity') is-invalid @enderror" required>
                <option value="">— Pilih —</option>
                @foreach(['low', 'medium', 'high', 'critical'] as $s)
                    <option value="{{ $s }}" {{ old('severity', $recall->severity) === $s ? 'selected' : '' }}>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>
            @error('severity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Produk Terkait</label>
            <select name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                <option value="">— Tidak terkait produk spesifik —</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id', $recall->product_id) == $product->id ? 'selected' : '' }}>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Brand Kendaraan Terkait</label>
            <select name="vehicle_brand_id" class="form-select @error('vehicle_brand_id') is-invalid @enderror">
                <option value="">— Tidak terkait brand spesifik —</option>
                @foreach($vehicleBrands as $brand)
                    <option value="{{ $brand->id }}" {{ old('vehicle_brand_id', $recall->vehicle_brand_id) == $brand->id ? 'selected' : '' }}>
                        {{ $brand->vehicle_brand }}
                    </option>
                @endforeach
            </select>
            @error('vehicle_brand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
            <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                rows="5" required>{{ old('description', $recall->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                    id="isActive" {{ old('is_active', $recall->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="isActive">Recall Aktif</label>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button type="submit" class="btn btn-danger px-4">
            <i class="fas fa-save me-1"></i>Simpan Perubahan
        </button>
        <a href="{{ route('recalls.index') }}" class="btn btn-outline-secondary ms-2">Batal</a>
    </div>
</form>
</div></div>
@endsection
