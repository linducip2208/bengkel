@extends('layouts.app')
@section('title', 'Buat Permintaan Pembelian')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Buat Permintaan Pembelian</h4>
    <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<form action="{{ route('purchase-requisitions.store') }}" method="POST" id="requisition-form">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <label class="form-label">No. Permintaan</label>
            <input type="text" class="form-control-plaintext" value="{{ $requisitionNumber }} (auto)" readonly>
        </div>
        <div class="col-md-3">
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
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Item yang Diminta</h6>
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
                            <th style="width:40%">Catatan</th>
                            <th style="width:5%" class="text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                    </tbody>
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
        <a href="{{ route('purchase-requisitions.index') }}" class="btn btn-outline-secondary">Batal</a>
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
                <input type="number" name="items[${idx}][quantity]" class="form-control form-control-sm text-center" value="${data.quantity || 1}" min="0.01" step="0.01" required>
            </td>
            <td>
                <input type="text" name="items[${idx}][notes]" class="form-control form-control-sm" value="${data.notes || ''}" placeholder="Catatan item">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove();">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);

        loadProductOptions(row.querySelector('.product-select'), data.product_id);
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

    document.getElementById('requisition-form').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length === 0) {
            e.preventDefault();
            alert('Minimal satu item harus ditambahkan.');
        }
    });
</script>
@endpush
