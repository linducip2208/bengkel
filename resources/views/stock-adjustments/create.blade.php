@extends('layouts.app')
@section('title', 'Request Stock Adjustment')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Request Stock Adjustment</h4>
    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('stock-adjustments.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Produk <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required onchange="updateStockInfo(this.value)">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                data-stock="{{ $product->current_stock }}"
                                data-unit="{{ $product->unit?->name ?? '' }}"
                                {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} (Stok: {{ $product->current_stock }} {{ $product->unit?->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Gudang</label>
                    <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                        <option value="">-- Pilih Gudang --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Cabang <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Cabang --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', session('current_branch_id')) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12">
                    <div class="alert alert-info mb-0" id="stockInfo" style="display:none;">
                        <strong>Stok Saat Ini:</strong> <span id="currentStockDisplay">-</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stok Baru <span class="text-danger">*</span></label>
                    <input type="number" name="new_quantity" class="form-control @error('new_quantity') is-invalid @enderror"
                           value="{{ old('new_quantity') }}" min="0" required
                           placeholder="Masukkan jumlah stok baru">
                    @error('new_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-12">
                    <label class="form-label">Alasan Penyesuaian <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="3"
                              required maxlength="1000"
                              placeholder="Jelaskan alasan penyesuaian stok...">{{ old('reason') }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Submit Request
                    </button>
                    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function updateStockInfo(productId) {
        var sel = document.querySelector('select[name="product_id"]');
        var option = sel.options[sel.selectedIndex];
        var stock = option.getAttribute('data-stock');
        var unit = option.getAttribute('data-unit');
        var info = document.getElementById('stockInfo');
        var display = document.getElementById('currentStockDisplay');

        if (stock !== null && stock !== undefined) {
            display.textContent = stock + ' ' + (unit || 'pcs');
            info.style.display = 'block';
        } else {
            info.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var sel = document.querySelector('select[name="product_id"]');
        if (sel.value) {
            updateStockInfo(sel.value);
        }
    });
</script>
@endpush
