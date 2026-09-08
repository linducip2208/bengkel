@php
    $key = $rowIndex ?? 0;
    $typeLabels = ['part' => 'PART', 'labor' => 'JASA', 'other' => 'MANUAL'];
    $rowType = $row['item_type'] ?? 'part';
    $rowDiscountType = $row['discount_type'] ?? 'fixed';
    $product = $row['product'] ?? null;
    $serviceCatalogId = $row['service_catalog_id'] ?? null;
    $catalogSelected = $product !== null || filled($serviceCatalogId);
    $canOverridePrice = $canOverridePrice ?? (bool) auth()->user()?->can('pos.price_override');
@endphp
<tr class="estimate-item-row" data-type="{{ $rowType }}" data-catalog-id="{{ $product?->id ?? $serviceCatalogId ?? '' }}" data-catalog-type="{{ $product ? 'part' : ($serviceCatalogId ? 'labor' : '') }}">
    <td data-label="Tipe">
        @if($rowType === 'other')
            <select name="items[{{ $key }}][item_type]" class="form-select form-select-sm est-type est-manual-type" aria-label="Tipe item manual">
                <option value="part" @selected($rowType === 'part')>Part</option>
                <option value="labor" @selected($rowType === 'labor')>Jasa</option>
                <option value="other" @selected($rowType === 'other')>Lainnya</option>
            </select>
        @else
            <span class="badge bg-{{ $rowType === 'part' ? 'primary' : 'info' }} row-type-label">{{ $typeLabels[$rowType] }}</span>
            <input type="hidden" name="items[{{ $key }}][item_type]" class="est-type" value="{{ $rowType }}">
        @endif
    </td>
    <td data-label="Item / Deskripsi">
        <div class="d-flex gap-1">
            <input type="text" name="items[{{ $key }}][description]" class="form-control form-control-sm est-desc" placeholder="Deskripsi item" value="{{ $row['description'] ?? '' }}" maxlength="500" required>
            <input type="hidden" name="items[{{ $key }}][product_id]" class="est-product-id" value="{{ $row['product_id'] ?? '' }}">
            <input type="hidden" name="items[{{ $key }}][service_catalog_id]" class="est-service-catalog-id" value="{{ $serviceCatalogId ?? '' }}">
            <button type="button" class="btn btn-sm btn-outline-primary catalog-trigger" title="Cari katalog" aria-label="Cari katalog"><i class="fas fa-search"></i></button>
        </div>
        <small class="item-meta text-muted">
            @if($product)
                <span>SKU: {{ $product->code ?: $product->product_no }}</span>
                <span class="catalog-stock {{ $product->available_stock <= 0 ? 'is-out' : '' }}"><i class="fas fa-box me-1"></i>{{ $product->available_stock <= 0 ? 'Stok habis' : 'Stok tersedia: '.rtrim(rtrim(number_format($product->available_stock, 3, ',', '.'), '0'), ',').' '.($product->unit?->abbreviation ?: $product->unit?->name ?: 'PCS') }}</span>
            @elseif($rowType === 'labor' && ! empty($row['serviceCatalog']))
                <span><i class="fas fa-clock me-1"></i>Durasi standar: {{ (int) round((float) $row['serviceCatalog']->estimated_hours * 60) }} menit</span>
            @elseif($rowType === 'labor')
                <span>Jasa manual — pilih dari master untuk mengisi harga dan durasi</span>
            @else
                <span>Item manual</span>
            @endif
        </small>
    </td>
    <td data-label="Qty"><input type="number" step="0.001" min="0.001" name="items[{{ $key }}][quantity]" class="form-control form-control-sm est-qty text-center" value="{{ $row['quantity'] ?? 1 }}" required></td>
    <td data-label="Harga"><input type="number" step="0.01" min="0" name="items[{{ $key }}][unit_price]" class="form-control form-control-sm est-price text-end" value="{{ $row['unit_price'] ?? 0 }}" {{ $catalogSelected && ! $canOverridePrice ? 'readonly' : '' }} required></td>
    <td data-label="Diskon">
        <div class="input-group input-group-sm">
            <input type="number" step="0.01" min="0" name="items[{{ $key }}][discount]" class="form-control est-disc text-end" value="{{ $row['discount'] ?? 0 }}">
            <select name="items[{{ $key }}][discount_type]" class="form-select est-disc-type" style="max-width:70px" aria-label="Jenis diskon item">
                <option value="fixed" @selected($rowDiscountType === 'fixed')>Rp</option>
                <option value="percent" @selected($rowDiscountType === 'percent')>%</option>
            </select>
        </div>
    </td>
    <td data-label="Pajak"><input type="number" step="0.01" min="0" max="100" name="items[{{ $key }}][tax_rate]" class="form-control form-control-sm est-tax text-center" value="{{ $row['tax_rate'] ?? '' }}"></td>
    <td data-label="Total" class="text-end est-line-total fw-semibold">Rp 0</td>
    <td data-label="Aksi" class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-estimate-row" title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-trash"></i></button></td>
</tr>
