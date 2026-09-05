@extends('layouts.app')
@section('title', 'Buat Invoice')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
<h4 class="mb-3">Buat Invoice Baru</h4>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Invoice belum disimpan.</strong>
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('invoices.store') }}" id="invoiceForm">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Pelanggan *</label>
            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                <option value="">Pilih Pelanggan</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" data-phone="{{ $customer->phone }}" data-email="{{ $customer->email }}"
                        {{ old('customer_id', $selectedService?->customer_id) == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Tipe Invoice *</label>
            <select name="invoice_type" class="form-select @error('invoice_type') is-invalid @enderror" required>
                <option value="service" {{ old('invoice_type') === 'service' ? 'selected' : '' }}>Service</option>
                <option value="sales" {{ old('invoice_type') === 'sales' ? 'selected' : '' }}>Sales</option>
                <option value="sales_part" {{ old('invoice_type') === 'sales_part' ? 'selected' : '' }}>Sales Part</option>
            </select>
            @error('invoice_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Tanggal *</label>
            <input type="date" name="invoice_date" class="form-control @error('invoice_date') is-invalid @enderror" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
            @error('invoice_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Berlaku Sampai</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', date('Y-m-d', strtotime('+14 days'))) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Metode Bayar</label>
            <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror">
                <option value="">Pilih</option>
                @foreach ($paymentMethods as $pm)
                    <option value="{{ $pm->id }}" {{ old('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                @endforeach
            </select>
            @error('payment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Service Ref</label>
            <input type="text" class="form-control" value="{{ $selectedService?->service_number }}" readonly>
            <input type="hidden" name="service_id" value="{{ old('service_id', $selectedService?->id) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kendaraan</label>
            <select name="vehicle_id" class="form-select vehicle-select @error('vehicle_id') is-invalid @enderror">
                <option value="">Pilih Kendaraan</option>
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}" data-model="{{ $v->model_name }}" data-plate="{{ $v->number_plate }}" data-odometer="{{ $v->odometer }}" data-customer="{{ $v->customer_id }}"
                        {{ old('vehicle_id', $selectedService?->vehicle_id) == $v->id ? 'selected' : '' }}>
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
                <button type="button" class="btn btn-sm btn-outline-info" onclick="openServicePicker()"><i class="bi bi-wrench"></i> Tambah Jasa Service</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addJasa()"><i class="bi bi-pencil"></i> Jasa Manual</button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPart()"><i class="bi bi-box"></i> Tambah Sparepart</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
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
                    @foreach (old('items', [['description' => '', 'quantity' => 1, 'unit_price' => 0]]) as $i => $item)
                    <tr>
        <td>
            <div class="d-flex gap-2">
                <input type="text" name="items[{{ $i }}][description]" class="form-control item-desc" value="{{ $item['description'] ?? '' }}" required>
                <input type="hidden" name="items[{{ $i }}][product_id]" class="product-id-input" value="{{ $item['product_id'] ?? '' }}">
                <button type="button" class="btn btn-sm btn-outline-secondary pick-product" title="Cari dari Inventory" onclick="openProductPicker(this)"><i class="bi bi-search"></i></button>
            </div>
            @error("items.$i.description") <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </td>
                        <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control qty @error("items.$i.quantity") is-invalid @enderror" value="{{ $item['quantity'] ?? 1 }}" min="0.01" step="0.01" oninput="calcRow(this)" required></td>
                        <td><input type="number" name="items[{{ $i }}][unit_price]" class="form-control price @error("items.$i.unit_price") is-invalid @enderror" value="{{ $item['unit_price'] ?? 0 }}" min="0" step="100" oninput="calcRow(this)" required></td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" name="items[{{ $i }}][discount]" class="form-control discount" value="{{ $item['discount'] ?? 0 }}" min="0" step="100" oninput="calcRow(this)">
                                <select name="items[{{ $i }}][discount_type]" class="form-select discount-type" style="max-width:56px;">
                                    <option value="fixed" {{ ($item['discount_type'] ?? 'fixed') === 'fixed' ? 'selected' : '' }}>Rp</option>
                                    <option value="percent" {{ ($item['discount_type'] ?? null) === 'percent' ? 'selected' : '' }}>%</option>
                                </select>
                            </div>
                        </td>
                        <td><input type="text" class="form-control row-total" readonly value="{{ number_format(($item['unit_price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.') }}"></td>
                        <td>
                            <input type="text" name="items[{{ $i }}][serial_number]" class="form-control form-control-sm" value="{{ $item['serial_number'] ?? '' }}" placeholder="Serial No...">
                            <input type="date" name="items[{{ $i }}][warranty_expiry]" class="form-control form-control-sm mt-1" value="{{ $item['warranty_expiry'] ?? '' }}">
                        </td>
                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted"><i class="bi bi-info-circle"></i> Gunakan tombol <i class="bi bi-box"></i> Sparepart untuk menambah dari Inventory, atau <i class="bi bi-wrench"></i> Jasa untuk input jasa service.</small>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">Diskon</label>
            <div class="input-group">
                <input type="number" name="discount" id="discountNominal" class="form-control @error('discount') is-invalid @enderror" value="{{ old('discount', 0) }}" min="0" step="1000" oninput="calcGrand()" placeholder="Nominal">
                <input type="number" name="discount_percent" id="discountPercent" class="form-control @error('discount_percent') is-invalid @enderror d-none" value="{{ old('discount_percent') }}" min="0" max="100" step="0.5" oninput="calcGrandPercent()" placeholder="Contoh: 10" aria-describedby="discountHelp">
                <select class="form-select" id="discountType" name="discount_type" style="max-width:90px;" onchange="toggleDiscountType()">
                    <option value="fixed" {{ old('discount_type', 'fixed') === 'fixed' ? 'selected' : '' }}>Rp</option>
                    <option value="percent" {{ old('discount_type') === 'percent' ? 'selected' : '' }}>%</option>
                </select>
            </div>
            <div id="discountHelp" class="form-text">Pilih <strong>%</strong>, lalu masukkan angka saja, contoh <strong>10</strong> untuk diskon 10%. Jangan masukkan tanda <strong>%</strong>.</div>
            @error('discount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            @error('discount_percent') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Pajak (Rp)</label>
            <input type="number" name="tax_amount" class="form-control @error('tax_amount') is-invalid @enderror" value="{{ old('tax_amount', 0) }}" min="0" step="1000" oninput="calcGrand()">
            @error('tax_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Down Payment (Rp)</label>
            <input type="number" name="dp_amount" class="form-control @error('dp_amount') is-invalid @enderror" value="{{ old('dp_amount', 0) }}" min="0" step="5000">
            @error('dp_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Grand Total</label>
            <input type="text" id="grandTotal" class="form-control fw-bold text-end" readonly value="Rp 0">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Invoice</button>
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>

{{-- Product Picker Modal --}}
<div class="modal fade" id="productPickerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-box"></i> Pilih Sparepart dari Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="productSearchInput" class="form-control" placeholder="Cari nama produk / kode...">
                </div>
                <div class="table-responsive">
                <table class="table table-hover table-sm" id="productTable">
                    <thead><tr><th>Kode</th><th>Nama Produk</th><th class="text-end">Stok</th><th class="text-end">Harga Jual</th><th></th></tr></thead>
                    <tbody id="productTableBody">
                        <tr><td colspan="5" class="text-center text-muted">Ketik untuk mencari produk...</td></tr>
                    </tbody>
                </table>
                </div>
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
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size:2rem;"></i>
                        <p class="mt-2">Belum ada paket service. <br><a href="{{ route('service-packages.index') }}" target="_blank">Tambah paket service di sini</a></p>
                    </div>
                @else
                <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead><tr><th>Nama Paket</th><th class="text-end">Estimasi</th><th class="text-end">Harga</th><th></th></tr></thead>
                    <tbody>
                        @foreach($packages as $pkg)
                        <tr>
                            <td>
                                <strong>{{ $pkg->name }}</strong>
                                @if($pkg->description)<br><small class="text-muted">{{ $pkg->description }}</small>@endif
                            </td>
                            <td class="text-end">{{ $pkg->estimated_hours ? $pkg->estimated_hours . ' jam' : '-' }}</td>
                            <td class="text-end fw-bold">@money($pkg->price)</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary"
                                    onclick="selectService('{{ $pkg->name }}', {{ $pkg->price }})">
                                    Pilih
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
                @endif
                <div class="mt-2 text-center">
                    <a href="{{ route('service-packages.index') }}" target="_blank" class="text-decoration-none">
                        <small><i class="bi bi-plus-circle"></i> Kelola Paket Service</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let itemIndex = {{ count(old('items', [0])) }};
let activeRow = null;

function addPart() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <div class="d-flex gap-2">
                <input type="text" name="items[${itemIndex}][description]" class="form-control item-desc" required placeholder="Nama sparepart...">
                <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-input" value="">
                <button type="button" class="btn btn-sm btn-outline-secondary pick-product" title="Cari dari Inventory" onclick="openProductPicker(this)"><i class="bi bi-search"></i></button>
            </div>
        </td>
        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="0.01" step="0.01" oninput="calcRow(this)" required></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price" value="0" min="0" step="100" oninput="calcRow(this)" required></td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" name="items[${itemIndex}][discount]" class="form-control discount" value="0" min="0" step="100" oninput="calcRow(this)">
                <select name="items[${itemIndex}][discount_type]" class="form-select discount-type" style="max-width:56px;">
                    <option value="fixed">Rp</option>
                    <option value="percent">%</option>
                </select>
            </div>
        </td>
        <td><input type="text" class="form-control row-total" readonly value="0"></td>
        <td>
            <input type="text" name="items[${itemIndex}][serial_number]" class="form-control form-control-sm" placeholder="Serial No...">
            <input type="date" name="items[${itemIndex}][warranty_expiry]" class="form-control form-control-sm mt-1">
        </td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    itemIndex++;
}

function openServicePicker() {
    new bootstrap.Modal(document.getElementById('servicePickerModal')).show();
}

function selectService(name, price) {
    if (activeRow) {
        activeRow.querySelector('.item-desc').value = name;
        activeRow.querySelector('.price').value = price;
        activeRow.querySelector('.row-total').value = Number(price).toLocaleString('id-ID');
        calcRow(activeRow.querySelector('.price'));
        bootstrap.Modal.getInstance(document.getElementById('servicePickerModal')).hide();
        activeRow = null;
        return;
    }
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <div class="d-flex gap-2">
                <input type="text" name="items[${itemIndex}][description]" class="form-control item-desc" value="${name}" required>
                <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-input" value="">
                <button type="button" class="btn btn-sm btn-outline-secondary pick-product opacity-0" style="pointer-events:none;"><i class="bi bi-search"></i></button>
            </div>
        </td>
        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="0.01" step="0.01" oninput="calcRow(this)" required></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price" value="${price}" min="0" step="5000" oninput="calcRow(this)" required></td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" name="items[${itemIndex}][discount]" class="form-control discount" value="0" min="0" step="100" oninput="calcRow(this)">
                <select name="items[${itemIndex}][discount_type]" class="form-select discount-type" style="max-width:56px;">
                    <option value="fixed">Rp</option>
                    <option value="percent">%</option>
                </select>
            </div>
        </td>
        <td><input type="text" class="form-control row-total" readonly value="${Number(price).toLocaleString('id-ID')}"></td>
        <td>
            <input type="text" name="items[${itemIndex}][serial_number]" class="form-control form-control-sm" placeholder="Serial No...">
            <input type="date" name="items[${itemIndex}][warranty_expiry]" class="form-control form-control-sm mt-1">
        </td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    itemIndex++;
    calcGrand();
    bootstrap.Modal.getInstance(document.getElementById('servicePickerModal')).hide();
}

function openServicePickerForRow(btn) {
    activeRow = btn.closest('tr');
    new bootstrap.Modal(document.getElementById('servicePickerModal')).show();
}

function addJasa() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <div class="d-flex gap-2">
                <input type="text" name="items[${itemIndex}][description]" class="form-control item-desc" required placeholder="Nama jasa service...">
                <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-input" value="">
                <button type="button" class="btn btn-sm btn-outline-secondary pick-product" onclick="openServicePickerForRow(this)"><i class="bi bi-search"></i></button>
            </div>
        </td>
        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="0.01" step="0.01" oninput="calcRow(this)" required></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price" value="0" min="0" step="5000" oninput="calcRow(this)" required placeholder="Harga jasa..."></td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" name="items[${itemIndex}][discount]" class="form-control discount" value="0" min="0" step="100" oninput="calcRow(this)">
                <select name="items[${itemIndex}][discount_type]" class="form-select discount-type" style="max-width:56px;">
                    <option value="fixed">Rp</option>
                    <option value="percent">%</option>
                </select>
            </div>
        </td>
        <td><input type="text" class="form-control row-total" readonly value="0"></td>
        <td>
            <input type="text" name="items[${itemIndex}][serial_number]" class="form-control form-control-sm" placeholder="Serial No...">
            <input type="date" name="items[${itemIndex}][warranty_expiry]" class="form-control form-control-sm mt-1">
        </td>
        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    itemIndex++;
}

function removeRow(btn) {
    const tbody = document.querySelector('#itemsTable tbody');
    if (tbody.querySelectorAll('tr').length > 1) {
        btn.closest('tr').remove();
        calcGrand();
    }
}

function openProductPicker(btn) {
    activeRow = btn.closest('tr');
    document.getElementById('productSearchInput').value = '';
    searchProducts('');
    new bootstrap.Modal(document.getElementById('productPickerModal')).show();
}

function searchProducts(q) {
    fetch('{{ route("products.search-json") }}?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('productTableBody');
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Produk tidak ditemukan.</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(p => `
                <tr>
                    <td><small class="text-muted">${p.code || '-'}</small></td>
                    <td>${p.name}</td>
                    <td class="text-end"><span class="badge bg-${p.stock_status === 'in_stock' ? 'success' : (p.stock_status === 'low' ? 'warning' : 'danger')}">${p.current_stock}</span></td>
                    <td class="text-end">${Number(p.price).toLocaleString('id-ID')}</td>
                    <td><button type="button" class="btn btn-sm btn-primary" onclick="selectProduct(this)" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">Pilih</button></td>
                </tr>
            `).join('');
        });
}

function selectProduct(btn) {
    const name = btn.dataset.name;
    const price = parseFloat(btn.dataset.price);
    const productId = btn.dataset.id;
    if (activeRow) {
        activeRow.querySelector('.item-desc').value = name;
        activeRow.querySelector('.price').value = price;
        const pidInput = activeRow.querySelector('.product-id-input');
        if (pidInput) pidInput.value = productId;
        calcRow(activeRow.querySelector('.price'));
    }
    bootstrap.Modal.getInstance(document.getElementById('productPickerModal')).hide();
    activeRow = null;
}

document.getElementById('productSearchInput').addEventListener('input', function() {
    const q = this.value.trim();
    if (q.length >= 1) {
        searchProducts(q);
    } else {
        searchProducts('');
    }
});

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
    const grand = Math.max(subtotal - discount + tax, 0);
    document.getElementById('grandTotal').value = 'Rp ' + grand.toLocaleString('id-ID');
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
    const grand = Math.max(subtotal - discount + tax, 0);
    document.getElementById('grandTotal').value = 'Rp ' + grand.toLocaleString('id-ID');
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
    // Run on load if customer already selected
    if (customerSelect.value) filterVehicles();
}
</script>
@endpush
