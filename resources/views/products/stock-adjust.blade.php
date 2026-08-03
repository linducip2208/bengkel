@extends('layouts.app')
@section('title', 'Stok Adjust: ' . $product->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Stok Adjust: {{ $product->name }}</h4>
    <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <small class="text-muted">Stok Saat Ini</small>
                <h2 class="mb-0
                    @if($product->current_stock <= 0) text-danger
                    @elseif($product->minimum_stock && $product->current_stock <= $product->minimum_stock) text-warning
                    @else text-success
                    @endif">
                    {{ $product->current_stock }}
                </h2>
                <small class="text-muted">{{ $product->unit?->name ?? '-' }}</small>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('products.stock-adjust', $product) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Tipe Penyesuaian <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="adjustment_type" id="type-add" value="add" {{ old('adjustment_type') == 'add' ? 'checked' : '' }} checked>
                            <label class="btn btn-outline-success" for="type-add">
                                <i class="bi bi-plus-circle"></i> Tambah Stok
                            </label>

                            <input type="radio" class="btn-check" name="adjustment_type" id="type-reduce" value="reduce" {{ old('adjustment_type') == 'reduce' ? 'checked' : '' }}>
                            <label class="btn btn-outline-danger" for="type-reduce">
                                <i class="bi bi-dash-circle"></i> Kurangi Stok
                            </label>

                            <input type="radio" class="btn-check" name="adjustment_type" id="type-set" value="set" {{ old('adjustment_type') == 'set' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="type-set">
                                <i class="bi bi-pencil-square"></i> Set Stok
                            </label>
                        </div>
                        @error('adjustment_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" class="form-control form-control-sm @error('quantity') is-invalid @enderror" value="{{ old('quantity') }}" required min="1" placeholder="Masukkan jumlah stok">
                        <small class="text-muted">Untuk "Set Stok", masukkan nilai stok akhir yang diinginkan.</small>
                        @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Alasan <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" rows="3" class="form-control form-control-sm @error('reason') is-invalid @enderror" required maxlength="500" placeholder="Jelaskan alasan penyesuaian stok...">{{ old('reason') }}</textarea>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Penyesuaian
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
