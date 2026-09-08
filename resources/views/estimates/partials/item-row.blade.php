@php
    $key = $rowIndex ?? 0;
    $typeLabels = ['part' => 'PART', 'labor' => 'JASA', 'other' => 'MANUAL'];
    $rowType = $row['item_type'] ?? 'part';
    $rowDiscountType = $row['discount_type'] ?? 'fixed';
    $product = $row['product'] ?? null;
    $canOverridePrice = $canOverridePrice ?? (bool) auth()->user()?->can('pos.price_override');
@endphp
<tr data-type="{{ $rowType }}" data-catalog-id="{{ $product?->id ?? '' }}" data-catalog-type="{{ $product ? 'part' : '' }}">
    <td>
        <span class="badge bg-{{ $rowType === 'part' ? 'primary' : ($rowType === 'labor' ? 'info' : 'secondary') }} row-type-label">{{ $typeLabels[$rowType] ?? 'MANUAL' }}</span>
        <input type="hidden" name="items[{{ $key }}][item_type]" class="est-type" value="{{ $rowType }}">
    </td>
    <td>
        <div class="d-flex gap-1">
            <input type="text" name="items[{{ $key }}][description]" class="form-control form-control-sm est-desc" placeholder="Deskripsi item" value="{{ $row['description'] ?? '' }}" maxlength="500">
            <input type="hidden" name="items[{{ $key }}][product_id]" class="est-product-id" value="{{ $row['product_id'] ?? '' }}">
            <input type="hidden" name="items[{{ $key }}][service_catalog_id]" class="est-service-catalog-id" value="{{ $row['service_catalog_id'] ?? '' }}">
            <button type="button" class="btn btn-sm btn-outline-primary catalog-trigger" title="Cari katalog" aria-label="Cari katalog"><i class="fas fa-search"></i></button>
        </div>
        <small class="item-meta text-muted">
            @if($product)
                <span>SKU: {{ $product->code ?: $product->product_no }}</span>
                <span class="catalog-stock {{ $product->available_stock <= 0 ? 'is-out' : '' }}"><i class="fas fa-box me-1"></i>{{ $product->available_stock <= 0 ? 'Stok habis' : 'Stok tersedia: '.rtrim(rtrim(number_format($product->available_stock, 3, ',', '.'), '0'), ',').' '.($product->unit?->abbreviation ?: $product->unit?->name ?: 'PCS') }}</span>
            @elseif($rowType === 'labor')
                <span>Jasa manual — pilih dari master untuk mengisi harga dan durasi</span>
            @else
                <span>Item manual</span>
            @endif
        </small>
    </td>
    <td><input type="number" step="0.001" min="0" name="items[{{ $key }}][quantity]" class="form-control form-control-sm est-qty text-center" value="{{ $row['quantity'] ?? 1 }}"></td>
    <td><input type="number" step="0.01" min="0" name="items[{{ $key }}][unit_price]" class="form-control form-control-sm est-price text-end" value="{{ $row['unit_price'] ?? 0 }}" {{ $product && ! $canOverridePrice ? 'readonly' : '' }}></td>
    <td>
        <div class="input-group input-group-sm">
            <input type="number" step="0.01" min="0" name="items[{{ $key }}][discount]" class="form-control est-disc text-end" value="{{ $row['discount'] ?? 0 }}">
            <select name="items[{{ $key }}][discount_type]" class="form-select est-disc-type" style="max-width:70px" aria-label="Jenis diskon item">
                <option value="fixed" @selected($rowDiscountType === 'fixed')>Rp</option>
                <option value="percent" @selected($rowDiscountType === 'percent')>%</option>
            </select>
        </div>
    </td>
    <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $key }}][tax_rate]" class="form-control form-control-sm est-tax text-center" value="{{ $row['tax_rate'] ?? '' }}"></td>
    <td class="text-end est-line-total fw-semibold">Rp 0</td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-estimate-row" title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-trash"></i></button></td>
</tr>
