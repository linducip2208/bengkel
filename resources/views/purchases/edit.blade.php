@extends('layouts.app')
@section('title', 'Edit Purchase Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Edit: {{ $purchase->purchase_no }}</h4>
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<form action="{{ route('purchases.update', $purchase) }}" method="POST" id="purchase-form">
    @csrf
    @method('PUT')

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">No. Purchase Order</label>
            <input type="text" class="form-control-plaintext" value="{{ $purchase->purchase_no }}" readonly>
        </div>
        <div class="col-md-4">
            <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
            <select name="supplier_id" id="supplier_id" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" required>
                <option value="">-- Pilih Supplier --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
            @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="purchase_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="purchase_date" id="purchase_date" class="form-control form-control-sm @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required>
            @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Item Pembelian</h6>
            <button type="button" class="btn btn-success btn-sm" onclick="addItemRow()">
                <i class="bi bi-plus-lg"></i> Tambah Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="items-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:35%">Produk</th>
                            <th style="width:15%" class="text-center">Jumlah</th>
                            <th style="width:20%" class="text-end">Harga Satuan (Rp)</th>
                            <th style="width:20%" class="text-end">Total (Rp)</th>
                            <th style="width:10%" class="text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody"></tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
                            <td class="text-end">
                                <strong id="grand-total">Rp 0</strong>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-12">
            <label for="notes" class="form-label">Catatan</label>
            <textarea name="notes" id="notes" rows="3" class="form-control form-control-sm">{{ old('notes', $purchase->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan Perubahan
        </button>
        <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let itemIndex = 0;

    function addItemRow(data = {}) {
        const tbody = document.getElementById('items-tbody');
        const idx = itemIndex++;
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.innerHTML = `
            <td>
                <select name="items[${idx}][product_id]" class="form-select form-select-sm product-select" required>
                    <option value="">-- Cari Produk --</option>
                </select>
            </td>
            <td>
                <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm text-center quantity-input" value="${data.quantity || 1}" min="1" required>
            </td>
            <td>
                <input type="number" name="items[${idx}][unit_price]" class="form-control form-control-sm text-end unit-price-input" value="${data.unit_price || 0}" min="0" required>
            </td>
            <td>
                <input type="text" class="form-control-plaintext form-control-sm text-end subtotal-display" value="Rp 0" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove(); recalculateTotal();">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);

        const select = row.querySelector('.product-select');
        loadProductOptions(select, data.product_id);

        const quantityInput = row.querySelector('.quantity-input');
        const priceInput = row.querySelector('.unit-price-input');
        quantityInput.addEventListener('input', () => updateSubtotal(row));
        priceInput.addEventListener('input', () => updateSubtotal(row));

        if (data.quantity || data.unit_price) {
            setTimeout(() => updateSubtotal(row), 100);
        }
    }

    function updateSubtotal(row) {
        const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
        const price = parseInt(row.querySelector('.unit-price-input').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.subtotal-display').value = 'Rp ' + subtotal.toLocaleString('id-ID');
        recalculateTotal();
    }

    function recalculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseInt(row.querySelector('.quantity-input').value) || 0;
            const price = parseInt(row.querySelector('.unit-price-input').value) || 0;
            total += qty * price;
        });
        document.getElementById('grand-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    function loadProductOptions(select, selectedId = null) {
        select.innerHTML = '<option value="">Loading...</option>';
        fetch('{{ route("products.search-json") }}?q=')
            .then(r => r.json())
            .then(products => {
                select.innerHTML = '<option value="">-- Cari Produk --</option>';
                products.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p.id;
                    option.textContent = `${p.code} - ${p.name} (Stok: ${p.current_stock})`;
                    if (selectedId && p.id == selectedId) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($purchase->items as $item)
            addItemRow({
                product_id: {{ $item->product_id }},
                quantity: {{ $item->quantity }},
                unit_price: {{ $item->unit_price }}
            });
        @endforeach
    });
</script>
@endpush
