@extends('layouts.app')
@section('title', 'Edit Invoice')

@section('content')
<h4 class="mb-3">Edit Invoice: {{ $invoice->invoice_number }}</h4>

@if($errors->any())
<div class="alert alert-danger">
    <strong>Gagal menyimpan:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('invoices.update', $invoice) }}" id="invoiceForm">
    @csrf
    @method('PUT')

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Pelanggan *</label>
            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                <option value="">Pilih Pelanggan</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id', $invoice->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Tipe Invoice *</label>
            <select name="invoice_type" class="form-select @error('invoice_type') is-invalid @enderror" required>
                <option value="service" {{ old('invoice_type', $invoice->invoice_type) === 'service' ? 'selected' : '' }}>Service</option>
                <option value="sales" {{ old('invoice_type', $invoice->invoice_type) === 'sales' ? 'selected' : '' }}>Sales</option>
                <option value="sales_part" {{ old('invoice_type', $invoice->invoice_type) === 'sales_part' ? 'selected' : '' }}>Sales Part</option>
            </select>
            @error('invoice_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Tanggal *</label>
            <input type="date" name="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror" value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
            @error('invoice_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Berlaku Sampai</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('Y-m-d') : '') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Metode Bayar</label>
            <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror">
                <option value="">Pilih</option>
                @foreach ($paymentMethods as $pm)
                    <option value="{{ $pm->id }}" {{ old('payment_method_id', $invoice->payment_method_id) == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                @endforeach
            </select>
            @error('payment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Service Ref</label>
            <input type="text" class="form-control" value="{{ $invoice->service?->service_number }}" readonly>
            <input type="hidden" name="service_id" value="{{ old('service_id', $invoice->service_id) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kendaraan</label>
            <select name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror">
                <option value="">Pilih Kendaraan</option>
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}" data-customer="{{ $v->customer_id }}" {{ old('vehicle_id', $invoice->vehicle_id) == $v->id ? 'selected' : '' }}>
                        {{ $v->number_plate }} — {{ $v->model_name }} ({{ $v->customer?->name ?? '-' }})
                    </option>
                @endforeach
            </select>
            @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Item Invoice</strong>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info" onclick="openServicePicker()"><i class="bi bi-wrench"></i> Tambah Jasa</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPart()"><i class="bi bi-box"></i> Tambah Sparepart</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addItem()"><i class="bi bi-plus"></i> Item Manual</button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th width="26%">Deskripsi *</th>
                        <th width="8%">Qty *</th>
                        <th width="14%">Harga Satuan (Rp)</th>
                        <th width="13%">Diskon</th>
                        <th width="14%">Total (Rp)</th>
                        <th width="20%">Serial / Garansi</th>
                        <th width="5%"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (old('items', $invoice->items) as $i => $item)
                    @php
                        $itQty = is_array($item) ? ($item['quantity'] ?? 1) : floatval($item->quantity ?? 1);
                        $itPrice = is_array($item) ? ($item['unit_price'] ?? 0) : floatval($item->unit_price ?? 0);
                        $itDisc = is_array($item) ? ($item['discount'] ?? 0) : floatval($item->discount ?? 0);
                        $itDiscType = is_array($item) ? ($item['discount_type'] ?? 'fixed') : ($item->discount_type ?? 'fixed');
                        $itSubtotal = $itQty * $itPrice;
                        $itDiscAmount = $itDiscType === 'percent' ? $itSubtotal * $itDisc / 100 : $itDisc;
                        $itLineTotal = max($itSubtotal - $itDiscAmount, 0);
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex gap-2">
                                <input type="text" name="items[{{ $i }}][description]" class="form-control item-desc" value="{{ is_array($item) ? $item['description'] : $item->description }}" required>
                                <input type="hidden" name="items[{{ $i }}][product_id]" class="product-id-input" value="{{ is_array($item) ? ($item['product_id'] ?? '') : ($item->product_id ?? '') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary pick-product" onclick="openProductPicker(this)"><i class="bi bi-search"></i></button>
                            </div>
                        </td>
                        <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control qty" value="{{ is_array($item) ? ($item['quantity'] ?? 1) : floatval($item->quantity) }}" min="0.01" step="0.01" oninput="calcRow(this)" required></td>
                        <td><input type="number" name="items[{{ $i }}][unit_price]" class="form-control price" value="{{ is_array($item) ? ($item['unit_price'] ?? 0) : floatval($item->unit_price) }}" min="0" step="100" oninput="calcRow(this)" required></td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" name="items[{{ $i }}][discount]" class="form-control discount" value="{{ $itDisc }}" min="0" step="100" oninput="calcRow(this)">
                                <select name="items[{{ $i }}][discount_type]" class="form-select discount-type" style="max-width:56px;">
                                    <option value="fixed" {{ $itDiscType === 'fixed' ? 'selected' : '' }}>Rp</option>
                                    <option value="percent" {{ $itDiscType === 'percent' ? 'selected' : '' }}>%</option>
                                </select>
                            </div>
                        </td>
                        <td><input type="text" class="form-control row-total" readonly value="{{ number_format($itLineTotal, 0, ',', '.') }}"></td>
                        <td>
                            <input type="text" name="items[{{ $i }}][serial_number]" class="form-control form-control-sm" value="{{ is_array($item) ? ($item['serial_number'] ?? '') : ($item->serial_number ?? '') }}" placeholder="Serial No...">
                            <input type="date" name="items[{{ $i }}][warranty_expiry]" class="form-control form-control-sm mt-1" value="{{ is_array($item) ? ($item['warranty_expiry'] ?? '') : ($item->warranty_expiry ? \Carbon\Carbon::parse($item->warranty_expiry)->format('Y-m-d') : '') }}">
                        </td>
                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Diskon</label>
            <div class="input-group">
                <input type="number" name="discount" id="discountNominal" class="form-control @error('discount') is-invalid @enderror" value="{{ old('discount', $invoice->discount) }}" min="0" step="1000" oninput="calcGrand()" placeholder="Nominal">
                <input type="number" name="discount_percent" id="discountPercent" class="form-control @error('discount_percent') is-invalid @enderror {{ ($invoice->discount_type ?? 'fixed') === 'fixed' ? 'd-none' : '' }}" value="{{ old('discount_percent', $invoice->discount_percent) }}" min="0" max="100" step="0.5" oninput="calcGrandPercent()" placeholder="Persen">
                <select class="form-select" id="discountType" name="discount_type" style="max-width:90px;" onchange="toggleDiscountType()">
                    <option value="fixed" {{ old('discount_type', $invoice->discount_type ?? 'fixed') === 'fixed' ? 'selected' : '' }}>Rp</option>
                    <option value="percent" {{ old('discount_type', $invoice->discount_type) === 'percent' ? 'selected' : '' }}>%</option>
                </select>
            </div>
            @error('discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Pajak (Rp)</label>
            <input type="number" name="tax_amount" class="form-control @error('tax_amount') is-invalid @enderror" value="{{ old('tax_amount', $invoice->tax_amount) }}" min="0" step="1000" oninput="calcGrand()">
            @error('tax_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Grand Total</label>
            <input type="text" id="grandTotal" class="form-control fw-bold text-end" readonly value="Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}">
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Down Payment (Rp)</label>
            <input type="number" name="dp_amount" class="form-control" value="{{ old('dp_amount', $invoice->dp_amount) }}" min="0" step="1000">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $invoice->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Invoice</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection

{{-- Product Picker Modal --}}
<div class="modal fade" id="productPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-box"></i> Pilih Sparepart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="productSearchInput" class="form-control mb-2" placeholder="Cari produk...">
                <table class="table table-hover table-sm"><thead><tr><th>Kode</th><th>Nama</th><th class="text-end">Stok</th><th class="text-end">Harga</th><th></th></tr></thead>
                <tbody id="productTableBody"><tr><td colspan="5" class="text-center text-muted">Ketik untuk mencari...</td></tr></tbody></table>
            </div>
        </div>
    </div>
</div>

{{-- Service Package Picker Modal --}}
<div class="modal fade" id="servicePickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-wrench"></i> Pilih Jasa Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @php $packages = \App\Models\ServicePackage::where('is_active', true)->orderBy('name')->get(); @endphp
                @if($packages->isEmpty())
                <div class="text-center text-muted py-4"><p>Belum ada paket service.</p></div>
                @else
                <table class="table table-hover table-sm">
                    <thead><tr><th>Nama Paket</th><th class="text-end">Harga</th><th></th></tr></thead>
                    <tbody>
                        @foreach($packages as $pkg)
                        <tr><td><strong>{{ $pkg->name }}</strong></td>
                            <td class="text-end fw-bold">@money($pkg->price)</td>
                            <td><button type="button" class="btn btn-sm btn-primary" onclick="selectService('{{ $pkg->name }}', {{ $pkg->price }})">Pilih</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let itemIndex = {{ count(old('items', $invoice->items)) }};
let activeRow = null;

function addPart() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><div class="d-flex gap-2"><input type="text" name="items[${itemIndex}][description]" class="form-control item-desc" required placeholder="Nama sparepart..."><input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-input" value=""><button type="button" class="btn btn-sm btn-outline-secondary pick-product" onclick="openProductPicker(this)"><i class="bi bi-search"></i></button></div></td><td><input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="0.01" step="0.01" oninput="calcRow(this)" required></td><td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price" value="0" min="0" step="100" oninput="calcRow(this)" required></td><td><div class="input-group input-group-sm"><input type="number" name="items[${itemIndex}][discount]" class="form-control discount" value="0" min="0" step="100" oninput="calcRow(this)"><select name="items[${itemIndex}][discount_type]" class="form-select discount-type" style="max-width:56px;"><option value="fixed">Rp</option><option value="percent">%</option></select></div></td><td><input type="text" class="form-control row-total" readonly value="0"></td><td><input type="text" name="items[${itemIndex}][serial_number]" class="form-control form-control-sm" placeholder="Serial No..."><input type="date" name="items[${itemIndex}][warranty_expiry]" class="form-control form-control-sm mt-1"></td><td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>`;
    tbody.appendChild(tr);
    itemIndex++;
}

function openServicePicker() { new bootstrap.Modal(document.getElementById('servicePickerModal')).show(); }

function selectService(name, price) {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input type="text" name="items[${itemIndex}][description]" class="form-control" value="${name}" required></td><td><input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="0.01" step="0.01" oninput="calcRow(this)" required></td><td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price" value="${price}" min="0" step="100" oninput="calcRow(this)" required></td><td><div class="input-group input-group-sm"><input type="number" name="items[${itemIndex}][discount]" class="form-control discount" value="0" min="0" step="100" oninput="calcRow(this)"><select name="items[${itemIndex}][discount_type]" class="form-select discount-type" style="max-width:56px;"><option value="fixed">Rp</option><option value="percent">%</option></select></div></td><td><input type="text" class="form-control row-total" readonly value="${Number(price).toLocaleString('id-ID')}"></td><td><input type="text" name="items[${itemIndex}][serial_number]" class="form-control form-control-sm" placeholder="Serial No..."><input type="date" name="items[${itemIndex}][warranty_expiry]" class="form-control form-control-sm mt-1"></td><td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>`;
    tbody.appendChild(tr);
    itemIndex++;
    calcGrand();
    bootstrap.Modal.getInstance(document.getElementById('servicePickerModal')).hide();
}

function addItem() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[${itemIndex}][description]" class="form-control" required></td>
         <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="0.01" step="0.01" oninput="calcRow(this)" required></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price" value="0" min="0" step="100" oninput="calcRow(this)" required></td>
        <td><div class="input-group input-group-sm"><input type="number" name="items[${itemIndex}][discount]" class="form-control discount" value="0" min="0" step="100" oninput="calcRow(this)"><select name="items[${itemIndex}][discount_type]" class="form-select discount-type" style="max-width:56px;"><option value="fixed">Rp</option><option value="percent">%</option></select></div></td>
        <td><input type="text" class="form-control row-total" readonly value="0"></td>
        <td><input type="text" name="items[${itemIndex}][serial_number]" class="form-control form-control-sm" placeholder="Serial No..."><input type="date" name="items[${itemIndex}][warranty_expiry]" class="form-control form-control-sm mt-1"></td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    itemIndex++;
}

function removeRow(btn) {
    const tbody = document.querySelector('#itemsTable tbody');
    if (tbody.querySelectorAll('tr').length > 1) btn.closest('tr').remove();
    calcGrand();
}

function lineTotal(row) {
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.price').value) || 0;
    const subtotal = qty * price;
    const discount = parseFloat(row.querySelector('.discount').value) || 0;
    const type = row.querySelector('.discount-type') ? row.querySelector('.discount-type').value : 'fixed';
    const d = type === 'percent' ? subtotal * discount / 100 : discount;
    return Math.max(subtotal - d, 0);
}

function calcRow(input) {
    const row = input.closest('tr');
    row.querySelector('.row-total').value = 'Rp ' + lineTotal(row).toLocaleString('id-ID');
    calcGrand();
}

function calcGrand() {
    let subtotal = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        subtotal += lineTotal(row);
    });
    const discount = parseFloat(document.querySelector('[name="discount"]').value) || 0;
    const tax = parseFloat(document.querySelector('[name="tax_amount"]').value) || 0;
    document.getElementById('grandTotal').value = 'Rp ' + Math.max(subtotal - discount + tax, 0).toLocaleString('id-ID');
}

function calcGrandPercent() {
    let subtotal = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        subtotal += lineTotal(row);
    });
    const pct = parseFloat(document.getElementById('discountPercent').value) || 0;
    const tax = parseFloat(document.querySelector('[name="tax_amount"]').value) || 0;
    const discount = Math.round(subtotal * pct / 100);
    document.querySelector('[name="discount"]').value = discount;
    document.getElementById('grandTotal').value = 'Rp ' + Math.max(subtotal - discount + tax, 0).toLocaleString('id-ID');
}

function toggleDiscountType() {
    const type = document.getElementById('discountType').value;
    document.getElementById('discountNominal').classList.toggle('d-none', type === 'percent');
    document.getElementById('discountPercent').classList.toggle('d-none', type === 'fixed');
    if (type === 'percent') calcGrandPercent(); else calcGrand();
}

document.addEventListener('DOMContentLoaded', () => {
    calcGrand();
    if (document.getElementById('discountType').value === 'percent') {
        document.getElementById('discountNominal').classList.add('d-none');
        document.getElementById('discountPercent').classList.remove('d-none');
    }
});

function openProductPicker(btn) { activeRow = btn.closest('tr'); document.getElementById('productSearchInput').value = ''; searchProducts(''); new bootstrap.Modal(document.getElementById('productPickerModal')).show(); }

function searchProducts(q) {
    fetch('{{ route("products.search-json") }}?q=' + encodeURIComponent(q))
        .then(r => r.json()).then(data => {
            const tbody = document.getElementById('productTableBody');
            if (!data.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Produk tidak ditemukan.</td></tr>'; return; }
            tbody.innerHTML = data.map(p => `<tr><td><small class="text-muted">${p.code||'-'}</small></td><td>${p.name}</td><td class="text-end"><span class="badge bg-${p.stock_status==='in_stock'?'success':(p.stock_status==='low'?'warning':'danger')}">${p.current_stock}</span></td><td class="text-end">${Number(p.price).toLocaleString('id-ID')}</td><td><button type="button" class="btn btn-sm btn-primary" onclick="selectProduct(this)" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">Pilih</button></td></tr>`).join('');
        });
}

function selectProduct(btn) {
    if (activeRow) { activeRow.querySelector('.item-desc').value = btn.dataset.name; activeRow.querySelector('.price').value = parseFloat(btn.dataset.price); const pid = activeRow.querySelector('.product-id-input'); if (pid) pid.value = btn.dataset.id; calcRow(activeRow.querySelector('.price')); }
    bootstrap.Modal.getInstance(document.getElementById('productPickerModal')).hide(); activeRow = null;
}

document.getElementById('productSearchInput').addEventListener('input', function() { searchProducts(this.value.trim() || ''); });

// Filter vehicle by customer
const customerSelect = document.querySelector('[name="customer_id"]');
const vehicleSelect = document.querySelector('[name="vehicle_id"]');
const allVehicleOptions = Array.from(vehicleSelect.querySelectorAll('option'));

function filterVehicles() {
    const customerId = customerSelect.value;
    vehicleSelect.value = '';
    vehicleSelect.innerHTML = '<option value="">Pilih Kendaraan</option>';
    allVehicleOptions.forEach(opt => {
        if (opt.value === '') return;
        if (!customerId || opt.dataset.customer === customerId) {
            vehicleSelect.appendChild(opt.cloneNode(true));
        }
    });
}

if (customerSelect) {
    customerSelect.addEventListener('change', filterVehicles);
    if (customerSelect.value) filterVehicles();
}
</script>
@endpush
