@extends('layouts.app')
@section('title', 'Edit Invoice')

@section('content')
<h4 class="mb-3">Edit Invoice: {{ $invoice->invoice_number }}</h4>

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
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Item Invoice</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addItem()"><i class="bi bi-plus"></i> Tambah Item</button>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th width="40%">Deskripsi *</th>
                        <th width="10%">Qty *</th>
                        <th width="20%">Harga Satuan (Rp)</th>
                        <th width="20%">Total (Rp)</th>
                        <th width="10%"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (old('items', $invoice->items) as $i => $item)
                    <tr>
                        <td><input type="text" name="items[{{ $i }}][description]" class="form-control" value="{{ is_array($item) ? $item['description'] : $item->description }}" required></td>
                        <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control qty" value="{{ is_array($item) ? ($item['quantity'] ?? 1) : $item->quantity }}" min="1" oninput="calcRow(this)" required></td>
                        <td><input type="number" name="items[{{ $i }}][unit_price]" class="form-control price" value="{{ is_array($item) ? ($item['unit_price'] ?? 0) : $item->unit_price }}" min="0" step="100" oninput="calcRow(this)" required></td>
                        <td><input type="text" class="form-control row-total" readonly value="{{ number_format((is_array($item) ? ($item['quantity'] * $item['unit_price'] ?? 0) : $item->total), 0, ',', '.') }}"></td>
                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Diskon (Rp)</label>
            <input type="number" name="discount" class="form-control @error('discount') is-invalid @enderror" value="{{ old('discount', $invoice->discount) }}" min="0" step="1000" oninput="calcGrand()">
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

@push('scripts')
<script>
let itemIndex = {{ count(old('items', $invoice->items)) }};

function addItem() {
    const tbody = document.querySelector('#itemsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[${itemIndex}][description]" class="form-control" required></td>
        <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control qty" value="1" min="1" oninput="calcRow(this)" required></td>
        <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price" value="0" min="0" step="100" oninput="calcRow(this)" required></td>
        <td><input type="text" class="form-control row-total" readonly value="0"></td>
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

function calcRow(input) {
    const row = input.closest('tr');
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.price').value) || 0;
    row.querySelector('.row-total').value = 'Rp ' + (qty * price).toLocaleString('id-ID');
    calcGrand();
}

function calcGrand() {
    let subtotal = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        subtotal += (parseFloat(row.querySelector('.qty').value) || 0) * (parseFloat(row.querySelector('.price').value) || 0);
    });
    const discount = parseFloat(document.querySelector('[name="discount"]').value) || 0;
    const tax = parseFloat(document.querySelector('[name="tax_amount"]').value) || 0;
    document.getElementById('grandTotal').value = 'Rp ' + Math.max(subtotal - discount + tax, 0).toLocaleString('id-ID');
}

document.addEventListener('DOMContentLoaded', calcGrand);
</script>
@endpush
