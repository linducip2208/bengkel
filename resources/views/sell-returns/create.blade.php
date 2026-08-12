@extends('layouts.app')
@section('title', 'Buat Retur Penjualan')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Buat Retur Penjualan</h4>
    <a href="{{ route('sell-returns.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<form action="{{ route('sell-returns.store') }}" method="POST" id="sell-return-form">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">No. Retur</label>
            <input type="text" class="form-control-plaintext" value="{{ $returnNumber }} (auto)" readonly>
        </div>
        <div class="col-md-3">
            <label for="return_date" class="form-label">Tanggal Retur <span class="text-danger">*</span></label>
            <input type="date" name="return_date" id="return_date" class="form-control form-control-sm @error('return_date') is-invalid @enderror" value="{{ old('return_date', date('Y-m-d')) }}" required>
            @error('return_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label for="sale_id" class="form-label">Dari Penjualan (opsional)</label>
            <select name="sale_id" id="sale_id" class="form-select form-select-sm @error('sale_id') is-invalid @enderror">
                <option value="">-- Walk-in --</option>
                @foreach($sales as $sale)
                    <option value="{{ $sale->id }}" {{ old('sale_id') == $sale->id ? 'selected' : '' }}>
                        {{ $sale->sales_no }} - {{ $sale->customer?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
            @error('sale_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label for="invoice_id" class="form-label">Dari Invoice (opsional)</label>
            <select name="invoice_id" id="invoice_id" class="form-select form-select-sm @error('invoice_id') is-invalid @enderror">
                <option value="">-- Walk-in --</option>
                @foreach($invoices as $invoice)
                    <option value="{{ $invoice->id }}" {{ old('invoice_id') == $invoice->id ? 'selected' : '' }}>
                        {{ $invoice->invoice_number }} - {{ $invoice->customer?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
            @error('invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label for="customer_id" class="form-label">Pelanggan (opsional)</label>
            <select name="customer_id" id="customer_id" class="form-select form-select-sm @error('customer_id') is-invalid @enderror">
                <option value="">-- Pilih Pelanggan --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Item yang Diretur</h6>
            <button type="button" class="btn btn-success btn-sm" onclick="addItemRow()">
                <i class="bi bi-plus-lg"></i> Tambah Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="items-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40%">Produk</th>
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
                            <td colspan="3" class="text-end"><strong>Total Refund</strong></td>
                            <td class="text-end"><strong id="total-display">Rp 0</strong></td>
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
            <label for="reason" class="form-label">Alasan Retur <span class="text-danger">*</span></label>
            <textarea name="reason" id="reason" rows="3" class="form-control form-control-sm @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan
        </button>
        <a href="{{ route('sell-returns.index') }}" class="btn btn-outline-secondary">Batal</a>
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
                    <option value="">-- Pilih Produk --</option>
                </select>
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

        loadProductOptions(row.querySelector('.product-select'), data.product_id);

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
        row.querySelector('.subtotal-display').value = 'Rp ' + (qty * price).toLocaleString('id-ID');
        recalculateTotal();
    }

    function recalculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.unit-price-input').value) || 0;
            total += qty * price;
        });
        document.getElementById('total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    function loadProductOptions(select, selectedId = null) {
        select.innerHTML = '<option value="">Loading...</option>';
        fetch('{{ route("products.search-json") }}?q=')
            .then(r => r.json())
            .then(products => {
                select.innerHTML = '<option value="">-- Pilih Produk --</option>';
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

    document.getElementById('sell-return-form').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Minimal satu item harus ditambahkan.');
        }
    });
</script>
@endpush
