@php
    $key = ($isTemplate ?? false) ? '__ROW__' : ($rowIndex ?? 0);
    $typeLabels = ['part' => 'Part', 'labor' => 'Jasa', 'other' => 'Manual'];
    $rowType = $row['item_type'] ?? 'part';
    $rowDiscountType = $row['discount_type'] ?? 'fixed';
@endphp
<tr data-type="{{ $rowType }}">
    <td class="ctr">
        <span class="row-idx">{{ ($rowIndex ?? 0) + 1 }}</span>
        <input type="hidden" name="items[{{ $key }}][item_type]" class="est-type" value="{{ $rowType }}">
        <div class="d-md-none small text-muted mt-1">{{ $typeLabels[$rowType] ?? $rowType }}</div>
    </td>
    <td>
        <select class="form-select form-select-sm est-product mb-1 d-none d-md-block" aria-label="Pilih produk">
            <option value="">— Manual / Tanpa Produk —</option>
            @foreach($products as $product)
            <option value="{{ $product->id }}"
                    data-price="{{ $product->price }}"
                    data-name="{{ $product->name }}"
                    @selected((string) ($row['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>
        <input type="hidden" name="items[{{ $key }}][product_id]" class="est-product-id" value="{{ $row['product_id'] ?? '' }}">
        <input type="text" name="items[{{ $key }}][description]" class="form-control form-control-sm est-desc" placeholder="Deskripsi item (mis. JASA O/H KOPLING)" value="{{ $row['description'] ?? '' }}" maxlength="500">
    </td>
    <td><input type="number" step="0.001" min="0" name="items[{{ $key }}][quantity]" class="form-control form-control-sm est-qty text-center" value="{{ $row['quantity'] ?? 1 }}"></td>
    <td><input type="number" step="0.01" min="0" name="items[{{ $key }}][unit_price]" class="form-control form-control-sm est-price text-end" placeholder="0" value="{{ $row['unit_price'] ?? '' }}"></td>
    <td>
        <div class="input-group input-group-sm">
            <input type="number" step="0.01" min="0" name="items[{{ $key }}][discount]" class="form-control est-disc text-end" value="{{ $row['discount'] ?? 0 }}">
            <select name="items[{{ $key }}][discount_type]" class="form-select est-disc-type" style="max-width:78px">
                <option value="fixed" @selected($rowDiscountType === 'fixed')>Rp</option>
                <option value="percent" @selected($rowDiscountType === 'percent')>%</option>
            </select>
        </div>
    </td>
    <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $key }}][tax_rate]" class="form-control form-control-sm est-tax text-center" placeholder="0" value="{{ $row['tax_rate'] ?? '' }}"></td>
    <td class="text-end est-line-total fw-semibold" style="white-space:nowrap">Rp 0</td>
    <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeEstimateRow(this)" title="Hapus baris"><i class="fas fa-trash"></i></button>
    </td>
</tr>
