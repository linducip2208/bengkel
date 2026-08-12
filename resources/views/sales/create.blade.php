@extends('layouts.app')
@section('title', 'Catat Penjualan Sparepart')

@section('content')
<h4 class="mb-3">Catat Penjualan Sparepart</h4>

<form method="POST" action="{{ route('sales.store') }}" id="saleForm">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Pelanggan</label>
            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                <option value="">Walk-in (Tanpa Pelanggan)</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal *</label>
            <input type="date" name="sale_date" class="form-control @error('sale_date') is-invalid @enderror" value="{{ old('sale_date', date('Y-m-d')) }}" required>
            @error('sale_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                <option value="completed" {{ old('status', 'completed') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Batal</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <input type="text" id="itemSearch" class="form-control" placeholder="Cari produk...">
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Item Sparepart</strong>
            <button type="button" class="btn btn-sm btn-primary" id="addRowBtn"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 260px;">Produk</th>
                            <th style="width: 120px;">Qty</th>
                            <th style="width: 160px;">Harga Satuan</th>
                            <th style="width: 160px;">Total</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
            @error('items') <div class="px-3 pb-2 text-danger small">{{ $message }}</div> @enderror
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="text-muted small">Grand Total</span>
            <h5 class="mb-0 text-primary fw-bold" id="grandTotal">Rp 0</h5>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>

@push('scripts')
@php
$productsJson = json_encode($products->map(fn($p) => [
    'id' => $p->id,
    'name' => $p->name,
    'code' => $p->code,
    'price' => (float) $p->price,
    'stock' => (int) ($p->stockRecord?->quantity ?? 0),
])->values());
$oldItemsJson = json_encode(old('items', []));
@endphp
<script>
(function () {
    const products = {!! $productsJson !!};

    const oldItems = {!! $oldItemsJson !!};

    const body = document.getElementById('itemsBody');
    let rowIndex = 0;

    function fmt(n) {
        return 'Rp ' + (parseFloat(n) || 0).toLocaleString('id-ID');
    }

    function optionsHTML(selectedId) {
        let html = '<option value="">-- Pilih Produk --</option>';
        products.forEach(p => {
            const sel = String(p.id) === String(selectedId) ? 'selected' : '';
            html += '<option value="' + p.id + '" data-price="' + p.price + '" data-stock="' + p.stock + '" ' + sel + '>' +
                p.name + ' (' + (p.code || '-') + ') — Stok: ' + p.stock + '</option>';
        });
        return html;
    }

    function addRow(productId, quantity, unitPrice) {
        const idx = rowIndex++;
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td>
                <select name="items[${idx}][product_id]" class="form-select form-select-sm product-select" required>
                    ${optionsHTML(productId)}
                </select>
            </td>
            <td><input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm qty" min="1" value="${quantity || 1}" required></td>
            <td><input type="number" name="items[${idx}][unit_price]" class="form-control form-control-sm unit-price" min="0" step="100" value="${unitPrice ?? ''}" required></td>
            <td><input type="text" class="form-control form-control-sm line-total" readonly></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
        `;
        body.appendChild(tr);
        recalcRow(tr);
    }

    function recalcRow(tr) {
        const qty = parseInt(tr.querySelector('.qty').value, 10) || 0;
        const price = parseFloat(tr.querySelector('.unit-price').value) || 0;
        tr.querySelector('.line-total').value = fmt(qty * price);
        recalcGrand();
    }

    function recalcGrand() {
        let total = 0;
        body.querySelectorAll('.item-row').forEach(tr => {
            const qty = parseInt(tr.querySelector('.qty').value, 10) || 0;
            const price = parseFloat(tr.querySelector('.unit-price').value) || 0;
            total += qty * price;
        });
        document.getElementById('grandTotal').textContent = fmt(total);
    }

    // Product change → autofill unit price
    body.addEventListener('change', (e) => {
        if (e.target.matches('.product-select')) {
            const opt = e.target.selectedOptions[0];
            const price = opt ? opt.dataset.price : '';
            e.target.closest('tr').querySelector('.unit-price').value = price || '';
            recalcRow(e.target.closest('tr'));
        } else if (e.target.matches('.qty') || e.target.matches('.unit-price')) {
            recalcRow(e.target.closest('tr'));
        }
    });

    body.addEventListener('click', (e) => {
        const btn = e.target.closest('.remove-row');
        if (btn) {
            btn.closest('tr').remove();
            recalcGrand();
        }
    });

    document.getElementById('addRowBtn').addEventListener('click', () => addRow('', 1, ''));

    // Filter product options across all selects
    document.getElementById('itemSearch').addEventListener('input', (e) => {
        const q = e.target.value.trim().toLowerCase();
        body.querySelectorAll('.product-select option').forEach(opt => {
            if (!opt.value) return;
            const match = opt.textContent.toLowerCase().includes(q);
            opt.hidden = !match;
        });
    });

    // Seed initial rows (old input on validation error, otherwise one blank row)
    if (oldItems && oldItems.length) {
        oldItems.forEach(it => addRow(it.product_id, it.quantity, it.unit_price));
    } else {
        addRow('', 1, '');
    }

    // Strip incomplete rows before submit so validation only sees valid items
    document.getElementById('saleForm').addEventListener('submit', () => {
        body.querySelectorAll('.item-row').forEach(tr => {
            const sel = tr.querySelector('.product-select');
            if (!sel.value) {
                tr.remove();
            }
        });
    });

    recalcGrand();
})();
</script>
@endpush
@endsection
