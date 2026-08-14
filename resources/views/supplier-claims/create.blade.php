@extends('layouts.app')
@section('title', 'Buat Klaim Supplier')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Buat Klaim Supplier</h4>
    <a href="{{ route('supplier-claims.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('supplier-claims.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                    <option value="">Pilih Supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Produk</label>
                <select name="product_id" class="form-select @error('product_id') is-invalid @enderror">
                    <option value="">Pilih Produk</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Klaim Garansi Terkait</label>
                <select name="warranty_claim_id" class="form-select @error('warranty_claim_id') is-invalid @enderror">
                    <option value="">Tanpa Klaim Garansi</option>
                    @foreach($warrantyClaims as $wc)
                        <option value="{{ $wc->id }}" {{ old('warranty_claim_id') == $wc->id ? 'selected' : '' }}>#{{ $wc->id }} — {{ $wc->complaint ? \Str::limit($wc->complaint, 40) : $wc->created_at?->format('d M Y') }}</option>
                    @endforeach
                </select>
                @error('warranty_claim_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Kuantitas</label>
                <input type="number" step="0.01" min="0" name="quantity" class="form-control @error('quantity') is-invalid @enderror" value="{{ old('quantity', 0) }}">
                @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Nilai Klaim <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="claim_amount" class="form-control @error('claim_amount') is-invalid @enderror" value="{{ old('claim_amount') }}" required>
                @error('claim_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-12">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
            <a href="{{ route('supplier-claims.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
