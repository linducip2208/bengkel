@extends('layouts.app')
@section('title', 'Buat Purchase Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Buat Purchase Order</h4>
    <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<form action="{{ route('purchase-orders.store') }}" method="POST" id="purchase-order-form">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">No. Purchase Order</label>
            <input type="text" class="form-control-plaintext" value="{{ $poNumber }} (auto)" readonly>
        </div>
        <div class="col-md-3">
            <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
            <select name="supplier_id" id="supplier_id" class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" required>
                <option value="">-- Pilih Supplier --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
            @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label for="order_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="order_date" id="order_date" class="form-control form-control-sm @error('order_date') is-invalid @enderror" value="{{ old('order_date', date('Y-m-d')) }}" required>
            @error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label for="expected_date" class="form-label">Estimasi Tiba</label>
            <input type="date" name="expected_date" id="expected_date" class="form-control form-control-sm @error('expected_date') is-invalid @enderror" value="{{ old('expected_date') }}">
            @error('expected_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label for="branch_id" class="form-label">Cabang</label>
            <select name="branch_id" id="branch_id" class="form-select form-select-sm @error('branch_id') is-invalid @enderror">
                <option value="">-- Pilih Cabang --</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select form-select-sm @error('status') is-invalid @enderror">
                <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="sent" {{ old('status') == 'sent' ? 'selected' : '' }}>Terkirim</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="tax_amount" class="form-label">Pajak (Rp)</label>
            <input type="number" name="tax_amount" id="tax_amount" step="0.01" class="form-control form-control-sm @error('tax_amount') is-invalid @enderror" value="{{ old('tax_amount', 0) }}" min="0">
            @error('tax_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Item Purchase Order</h6>
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
                            <th style="width:25%">Deskripsi</th>
                            <th style="width:15%" class="text-center">Jumlah</th>
                            <th style="width:20%" class="text-end">Harga Satuan</th>
                            <th style="width:20%" class="text-end">Total</th>
                            <th style="width:5%" class="text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end"><strong>Subtotal</strong></td>
                            <td class="text-end"><strong id="subtotal-display">Rp 0</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @error('items')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row g-3">
        <div class="col-md-12">
            <label for="notes" class="form-label">Catatan</label>
            <textarea name="notes" id="notes" rows="3" class="form-control form-control-sm @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan
        </button>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let itemIndex = {{ count(old('items', [])) }};

    function addItemRow(data = {}) {
        const tbody = document.getElementById('items-tbody');
        const idx = itemIndex++;
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.innerHTML = `
            <td>
                <select name="items[${idx}][product_id]" class="form-select form-select-sm product-select">
                    <option value="">-- Tanpa Produk --</option>
                </select>
            </td>
            <td>
                <input type="text" name="items[${idx}][description]" class="form-control form-control-sm description-input" value="${data.description || ''}" placeholder="Deskripsi item">
            </td>
            <td>
                <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm text-center quantity-input" value="${data.quantity || 1}" min="0.01" step="0.01" required>
            </td>
            <td>
                <input type="number" name="items[${idx}][unit_price]" class="form-control form-control-sm text-end unit-price-input" value="${data.unit_price || 0}" min="0" step="0.01" required>
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

        ['quantity-input', 'unit-price-input'].forEach(cls => {
            row.querySelector('.' + cls).addEventListener('input', () => updateSubtotal(row));
        });

        if (data.quantity || data.unit_price) {
            updateSubtotal(row);
        }
    }

    function updateSubtotal(row) {
        const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.unit-price-input').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.subtotal-display').value = 'Rp ' + subtotal.toLocaleString('id-ID');
        recalculateTotal();
    }

    function recalculateTotal() {
        let subtotal = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.unit-price-input').value) || 0;
            subtotal += qty * price;
        });
        const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
        document.getElementById('subtotal-display').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    }

    function loadProductOptions(select, selectedId = null) {
        select.innerHTML = '<option value="">Loading...</option>';
        fetch('{{ route("products.search-json") }}?q=')
            .then(r => r.json())
            .then(products => {
                select.innerHTML = '<option value="">-- Tanpa Produk --</option>';
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

    document.getElementById('tax_amount').addEventListener('input', recalculateTotal);

    document.getElementById('purchase-order-form').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Minimal satu item harus ditambahkan.');
        }
    });
</script>
@endpush
