@php
    /** @var \App\Models\ServiceEstimate|null $builderEstimate */
    $builderEstimate = $builderEstimate ?? null;
    $availablePackages = $availablePackages ?? collect();
    $canOverridePrice = $canOverridePrice ?? (bool) auth()->user()?->can('pos.price_override');
    $oldItems = old('items');
    if ($oldItems !== null) {
        $editItems = is_array($oldItems) ? $oldItems : [];
    } elseif ($builderEstimate?->isEditable()) {
        $editItems = $builderEstimate->items->map(fn ($item) => [
            'item_type' => $item->item_type,
            'product_id' => $item->product_id,
            'product' => $item->product,
            'description' => $item->description,
            'quantity' => (string) $item->quantity,
            'unit_price' => (string) $item->unit_price,
            'discount' => (string) $item->discount,
            'discount_type' => $item->discount_type,
            'tax_rate' => $item->tax_rate,
        ])->all();
    } else {
        $editItems = [];
    }
@endphp

<div class="estimate-builder border-top pt-3 mt-3">
    <form method="POST" id="estimateForm" action="{{ $builderEstimate?->isEditable() ? route('estimates.update', $builderEstimate) : route('services.estimates.store', $service) }}">
        @csrf
        @if($builderEstimate?->isEditable()) @method('PUT') @endif
        <input type="hidden" name="redirect_to" value="estimates">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h6 class="mb-1"><i class="fas fa-pen-to-square me-1 text-warning"></i>{{ $builderEstimate?->isEditable() ? 'Edit Estimasi' : 'Item Estimasi' }}</h6>
            <small class="text-muted">Informasi harga kepada pelanggan sebelum pekerjaan dilakukan.</small>
        </div>
        <span class="badge bg-light text-dark border"><i class="fas fa-shield-halved me-1"></i>Draft dapat diedit</span>
    </div>

    {{-- Optional technical recommendations remain available without blocking direct estimates. --}}
    @if($availablePackages->isNotEmpty())
    <div class="border rounded p-3 mb-3 bg-light" aria-label="Rencana Pekerjaan">
        <span class="visually-hidden">WORK PACKAGE</span>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <strong><i class="fas fa-list-check me-1 text-warning"></i>Rencana Pekerjaan</strong>
                <small class="text-muted d-block">Pilih pekerjaan dari temuan untuk dikelompokkan dalam persetujuan pelanggan.</small>
            </div>
            <a href="{{ route('services.show', $service) }}#tab-findings" class="btn btn-sm btn-outline-warning"><i class="fas fa-arrow-up-right-from-square me-1"></i>Lihat Pemeriksaan</a>
        </div>
        <div class="row g-2">
            @foreach($availablePackages as $package)
                @php
                    $packageTotals = $package->computeTotals();
                    $selectedPackage = $builderEstimate?->groups?->contains('service_work_package_id', $package->id) ?? ! $builderEstimate;
                @endphp
                <div class="col-12 col-lg-6">
                    <label class="estimate-package-option border rounded p-2 d-flex justify-content-between align-items-start gap-2 bg-white h-100">
                        <span class="small">
                            <input type="checkbox" name="packages[]" value="{{ $package->id }}" class="form-check-input me-1" @checked($selectedPackage)>
                            <strong>{{ $package->title }}</strong>
                            @if($package->finding)
                                <span class="badge bg-warning text-dark ms-1">dari {{ $package->finding->finding_number }}</span>
                            @else
                                <span class="badge bg-secondary ms-1">Manual</span>
                            @endif
                            <small class="text-muted d-block ms-4">{{ $packageTotals['standard_minutes'] }} menit · Jasa Rp {{ number_format($packageTotals['labor_total'], 0, ',', '.') }} · Part Rp {{ number_format($packageTotals['part_total'], 0, ',', '.') }}</small>
                        </span>
                        <strong class="text-nowrap">Rp {{ number_format($packageTotals['grand_total'], 0, ',', '.') }}</strong>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
    @endif

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <label class="form-label small" for="estimateDate">Tanggal Estimasi</label>
                <input id="estimateDate" type="date" name="estimate_date" class="form-control form-control-sm" value="{{ old('estimate_date', ($builderEstimate?->estimate_date ?? now())->format('Y-m-d')) }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small" for="validUntil">Berlaku Sampai</label>
                <input id="validUntil" type="date" name="valid_until" class="form-control form-control-sm" value="{{ old('valid_until', ($builderEstimate?->valid_until ?? now()->addDays(7))->format('Y-m-d')) }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="estimateDiscount">Diskon Dokumen</label>
                <div class="input-group input-group-sm">
                    <input id="estimateDiscount" type="number" step="0.01" min="0" name="discount" class="form-control" value="{{ old('discount', $builderEstimate?->discount ?? 0) }}">
                    <select name="discount_type" class="form-select" style="max-width:78px" aria-label="Jenis diskon dokumen">
                        <option value="fixed" @selected(old('discount_type', $builderEstimate?->discount_type ?? 'fixed') === 'fixed')>Rp</option>
                        <option value="percent" @selected(old('discount_type', $builderEstimate?->discount_type ?? 'fixed') === 'percent')>%</option>
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small" for="estimateNotes">Catatan</label>
                <input id="estimateNotes" type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes', $builderEstimate?->notes) }}" placeholder="Keluhan / catatan pemeriksaan">
            </div>
            <div class="col-12">
                <label class="form-label small" for="estimateTerms">Syarat / Terms</label>
                <textarea id="estimateTerms" name="terms" rows="2" class="form-control form-control-sm" placeholder="Syarat estimasi yang akan dibaca pelanggan">{{ old('terms', $builderEstimate?->terms ?? app(\App\Services\EstimateService::class)->defaultTerms()) }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <strong><i class="fas fa-list me-1"></i>ITEM ESTIMASI</strong>
                <small class="text-muted d-block d-md-inline ms-md-2">Sparepart, jasa, dan item lain yang ditawarkan.</small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-estimate-item="part"><i class="fas fa-cog me-1"></i>+ Sparepart</button>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-estimate-item="labor"><i class="fas fa-wrench me-1"></i>+ Jasa</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-add-estimate-item="other"><i class="fas fa-plus me-1"></i>+ Item Manual</button>
            </div>
        </div>

        <div class="estimate-items-scroll table-responsive">
            <table class="table table-sm table-bordered align-middle" id="estimateItems">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:112px">Tipe</th>
                        <th style="min-width:250px">Item / Deskripsi</th>
                        <th style="min-width:78px">Qty</th>
                        <th style="min-width:130px">Harga</th>
                        <th style="min-width:120px">Diskon</th>
                        <th style="min-width:78px">Pajak %</th>
                        <th class="text-end" style="min-width:130px">Total</th>
                        <th class="text-center" style="min-width:54px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($editItems as $rowIndex => $row)
                        @include('estimates.partials.item-row', ['row' => $row, 'rowIndex' => $rowIndex, 'canOverridePrice' => $canOverridePrice])
                    @endforeach
                </tbody>
            </table>
        </div>
        <div id="estimateItemsEmpty" class="border border-dashed rounded p-4 text-center text-muted {{ count($editItems) > 0 ? 'd-none' : '' }}">
            <i class="fas fa-cart-plus fa-2x mb-2"></i>
            <div>Belum ada item. Pilih <strong>Sparepart</strong> atau <strong>Jasa</strong> untuk mengisi estimasi.</div>
        </div>

        <div class="row justify-content-end mb-3 mt-3">
            <div class="col-12 col-md-5 col-lg-4">
                <table class="table table-sm mb-0" id="estimateTotals">
                    <tr><td class="text-muted">Subtotal</td><td class="text-end fw-semibold" id="live-subtotal">Rp 0</td></tr>
                    <tr><td class="text-muted">Diskon</td><td class="text-end" id="live-discount">Rp 0</td></tr>
                    <tr><td class="text-muted">Pajak</td><td class="text-end" id="live-tax">Rp 0</td></tr>
                    <tr class="table-dark"><td class="fw-bold">TOTAL ESTIMASI</td><td class="text-end fw-bold" id="live-grand">Rp 0</td></tr>
                </table>
                <small class="text-muted d-block mt-2"><i class="fas fa-shield-halved me-1"></i>Stok hanya informasi ketersediaan. Menyimpan estimasi tidak mengurangi stok dan tidak membuat jurnal.</small>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 flex-wrap mb-2">
            <a href="{{ route('services.show', $service) }}#tab-estimate" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>{{ $builderEstimate?->isEditable() ? 'Simpan Perubahan' : 'Simpan Draft' }}</button>
        </div>
    </form>
</div>

<div class="modal fade" id="estimateCatalogModal" tabindex="-1" aria-labelledby="estimateCatalogTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="estimateCatalogTitle">Tambah Sparepart</h5>
                    <small class="text-muted" id="estimateCatalogHint">Pilih dari master produk.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <label class="visually-hidden" for="estimateCatalogSearch">Cari katalog</label>
                <input type="search" id="estimateCatalogSearch" class="form-control mb-2" placeholder="Cari nama, SKU, barcode, atau part number..." autocomplete="off">
                <div class="d-flex gap-2 mb-3" id="estimateProductFilters">
                    <button type="button" class="btn btn-sm btn-primary" data-stock="all">Semua</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-stock="available">Tersedia</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-stock="out">Habis</button>
                </div>
                <div id="estimateCatalogResults" class="list-group">
                    <div class="text-center text-muted py-4">Ketik minimal 1 karakter untuk mencari.</div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary d-none w-100 mt-3" id="estimateCatalogLoadMore">Muat lebih banyak</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .estimate-package-option { cursor: pointer; transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease; }
    .estimate-package-option:hover, .estimate-package-option:has(input:checked) { border-color: #f59e0b !important; box-shadow: 0 4px 12px rgba(15, 23, 42, .08); transform: translateY(-1px); }
    .estimate-items-scroll { -webkit-overflow-scrolling: touch; }
    .estimate-builder .form-control, .estimate-builder .form-select { min-height: 38px; }
    .estimate-builder .item-meta { line-height: 1.35; }
    .estimate-builder .catalog-stock { font-size: .75rem; }
    .estimate-builder .catalog-stock.is-out { color: #dc2626; }
    @media (max-width: 640px) {
        .estimate-builder .btn { min-height: 40px; }
        .estimate-builder .table { min-width: 980px; }
        .estimate-builder .table td, .estimate-builder .table th { padding: .6rem .5rem; }
        .estimate-builder .form-control, .estimate-builder .form-select { font-size: .875rem; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';
    const form = document.getElementById('estimateForm');
    const modalEl = document.getElementById('estimateCatalogModal');
    if (!form || !modalEl || typeof bootstrap === 'undefined') return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const tbody = form.querySelector('#estimateItems tbody');
    const emptyState = form.querySelector('#estimateItemsEmpty');
    const searchInput = document.getElementById('estimateCatalogSearch');
    const results = document.getElementById('estimateCatalogResults');
    const loadMore = document.getElementById('estimateCatalogLoadMore');
    const title = document.getElementById('estimateCatalogTitle');
    const hint = document.getElementById('estimateCatalogHint');
    const productFilters = document.getElementById('estimateProductFilters');
    const productUrl = @json(route('estimates.catalog.products'));
    const serviceUrl = @json(route('estimates.catalog.services'));
    const serviceId = @json($service->id);
    const canOverridePrice = @json($canOverridePrice);
    let activeRow = null, catalogType = 'part', stockFilter = 'all', page = 1, timer = null;

    function esc(value) { return String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
    function money(value) { return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }); }
    function number(value) { return Number(value) || 0; }
    function rowKey() { return 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1000000); }

    function productCard(item) {
        const stock = number(item.stock_available);
        const stockClass = stock <= 0 ? 'is-out' : '';
        const stockText = stock <= 0 ? 'Stok habis — tetap dapat ditawarkan' : 'Stok tersedia: ' + stock + ' ' + esc(item.unit || 'PCS');
        return '<button type="button" class="list-group-item list-group-item-action text-start catalog-result" data-catalog="' + esc(JSON.stringify(item)) + '">'
            + '<div class="d-flex justify-content-between gap-3"><span><strong>' + esc(item.name) + '</strong><small class="d-block text-muted">SKU: ' + esc(item.sku || item.part_number || '-') + (item.barcode ? ' · Barcode: ' + esc(item.barcode) : '') + '</small></span><strong class="text-nowrap">' + money(item.selling_price) + '</strong></div>'
            + '<small class="catalog-stock ' + stockClass + '"><i class="fas fa-box me-1"></i>' + stockText + '</small>'
            + '</button>';
    }
    function serviceCard(item) {
        const duration = item.standard_minutes ? '<span class="text-muted"> · ' + esc(item.standard_minutes) + ' menit</span>' : '';
        return '<button type="button" class="list-group-item list-group-item-action text-start catalog-result" data-catalog="' + esc(JSON.stringify(item)) + '">'
            + '<div class="d-flex justify-content-between gap-3"><span><strong>' + esc(item.name) + '</strong><small class="d-block text-muted">' + esc(item.category || item.description || 'Jasa bengkel') + duration + '</small></span><strong class="text-nowrap">' + money(item.price) + '</strong></div>'
            + '</button>';
    }
    function renderCatalog(items, append) {
        const html = items.map(catalogType === 'part' ? productCard : serviceCard).join('');
        if (!append) results.innerHTML = html || '<div class="text-center text-muted py-4">Katalog tidak menemukan item yang cocok.</div>';
        else if (html) results.insertAdjacentHTML('beforeend', html);
    }
    async function loadCatalog(reset) {
        if (reset) page = 1;
        const query = searchInput.value.trim();
        if (query.length < 1) {
            results.innerHTML = '<div class="text-center text-muted py-4">Ketik minimal 1 karakter untuk mencari.</div>';
            loadMore.classList.add('d-none');
            return;
        }
        results.innerHTML = reset ? '<div class="text-center text-muted py-4">Memuat katalog...</div>' : results.innerHTML;
        const baseUrl = catalogType === 'part' ? productUrl : serviceUrl;
        const params = new URLSearchParams({ q: query, page: page });
        if (catalogType === 'part') { params.set('stock', stockFilter); params.set('service_id', serviceId); }
        try {
            const response = await fetch(baseUrl + '?' + params.toString(), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('catalog');
            const data = await response.json();
            renderCatalog(data.results || [], !reset);
            loadMore.classList.toggle('d-none', !(data.pagination && data.pagination.more));
        } catch (error) {
            results.innerHTML = '<div class="text-center text-danger py-4">Gagal memuat katalog. Coba lagi.</div>';
            loadMore.classList.add('d-none');
        }
    }
    function openCatalog(type, row) {
        catalogType = type; activeRow = row || null; stockFilter = 'all'; page = 1;
        title.textContent = type === 'part' ? 'Tambah Sparepart' : 'Tambah Jasa';
        hint.textContent = type === 'part' ? 'Harga jual dan stok diambil dari master produk.' : 'Pilih jasa dari master jasa/package.';
        productFilters.classList.toggle('d-none', type !== 'part');
        searchInput.value = ''; results.innerHTML = '<div class="text-center text-muted py-4">Ketik minimal 1 karakter untuk mencari.</div>';
        loadMore.classList.add('d-none'); modal.show();
        setTimeout(() => searchInput.focus(), 150);
    }
    function findExisting(field, value, ignored) {
        return Array.from(tbody.querySelectorAll('tr')).find(row => row !== ignored && row.querySelector(field)?.value === String(value));
    }
    function setMeta(row, item, type) {
        row.dataset.catalogId = item.id || '';
        row.dataset.catalogType = type;
        const meta = row.querySelector('.item-meta');
        if (type === 'part') {
            meta.innerHTML = '<span>SKU: ' + esc(item.sku || item.part_number || '-') + '</span><span class="catalog-stock ' + (number(item.stock_available) <= 0 ? 'is-out' : '') + '"><i class="fas fa-box me-1"></i>' + (number(item.stock_available) <= 0 ? 'Stok habis' : 'Stok tersedia: ' + number(item.stock_available) + ' ' + esc(item.unit || 'PCS')) + '</span>';
        } else {
            meta.innerHTML = item.standard_minutes ? '<span><i class="fas fa-clock me-1"></i>Durasi standar: ' + esc(item.standard_minutes) + ' menit</span>' : '<span>Jasa master</span>';
        }
    }
    function selectCatalog(item) {
        let row = activeRow;
        const field = catalogType === 'part' ? '.est-product-id' : '.est-service-catalog-id';
        const existing = findExisting(field, item.id, row);
        if (existing) {
            const qty = existing.querySelector('.est-qty'); qty.value = number(qty.value) + 1; updateLiveTotals(); modal.hide(); activeRow = null; return;
        }
        if (!row) row = addRow(catalogType);
        row.querySelector('.est-type').value = catalogType;
        row.querySelector('.est-desc').value = item.name || '';
        row.querySelector('.est-price').value = number(catalogType === 'part' ? item.selling_price : item.price);
        row.querySelector('.est-product-id').value = catalogType === 'part' ? item.id : '';
        row.querySelector('.est-service-catalog-id').value = catalogType === 'labor' ? item.id : '';
        row.querySelector('.est-qty').value = row.querySelector('.est-qty').value || 1;
        row.querySelector('.est-price').readOnly = catalogType === 'part' && !canOverridePrice;
        setMeta(row, item, catalogType);
        updateLiveTotals(); modal.hide(); activeRow = null;
    }
    function rowHtml(key, type) {
        return '<tr data-type="' + type + '">'
            + '<td><span class="badge bg-' + (type === 'part' ? 'primary' : (type === 'labor' ? 'info' : 'secondary')) + ' row-type-label">' + (type === 'part' ? 'PART' : (type === 'labor' ? 'JASA' : 'MANUAL')) + '</span><input type="hidden" name="items[' + key + '][item_type]" class="est-type" value="' + type + '"></td>'
            + '<td><div class="d-flex gap-1"><input type="text" name="items[' + key + '][description]" class="form-control form-control-sm est-desc" placeholder="Deskripsi item" maxlength="500"><input type="hidden" name="items[' + key + '][product_id]" class="est-product-id" value=""><input type="hidden" name="items[' + key + '][service_catalog_id]" class="est-service-catalog-id" value=""><button type="button" class="btn btn-sm btn-outline-primary catalog-trigger" title="Cari katalog"><i class="fas fa-search"></i></button></div><small class="item-meta text-muted"></small></td>'
            + '<td><input type="number" step="0.001" min="0" name="items[' + key + '][quantity]" class="form-control form-control-sm est-qty text-center" value="1"></td>'
            + '<td><input type="number" step="0.01" min="0" name="items[' + key + '][unit_price]" class="form-control form-control-sm est-price text-end" value="0"></td>'
            + '<td><div class="input-group input-group-sm"><input type="number" step="0.01" min="0" name="items[' + key + '][discount]" class="form-control est-disc text-end" value="0"><select name="items[' + key + '][discount_type]" class="form-select est-disc-type" style="max-width:70px"><option value="fixed">Rp</option><option value="percent">%</option></select></div></td>'
            + '<td><input type="number" step="0.01" min="0" max="100" name="items[' + key + '][tax_rate]" class="form-control form-control-sm est-tax text-center" value=""></td>'
            + '<td class="text-end est-line-total fw-semibold">Rp 0</td><td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-estimate-row" title="Hapus baris" aria-label="Hapus baris"><i class="fas fa-trash"></i></button></td>'
            + '</tr>';
    }
    function addRow(type) { const key = rowKey(), wrap = document.createElement('tbody'); wrap.innerHTML = rowHtml(key, type); const row = wrap.firstElementChild; tbody.appendChild(row); emptyState.classList.add('d-none'); updateTrigger(row); return row; }
    function updateTrigger(row) { row.querySelector('.catalog-trigger').onclick = () => openCatalog(row.querySelector('.est-type').value === 'labor' ? 'labor' : 'part', row); }
    function calcRow(row) { const qty = number(row.querySelector('.est-qty').value), price = number(row.querySelector('.est-price').value), disc = number(row.querySelector('.est-disc').value), type = row.querySelector('.est-disc-type').value, taxRate = number(row.querySelector('.est-tax').value); const base = qty * price, discount = Math.min(type === 'percent' ? base * disc / 100 : disc, base), tax = (base - discount) * taxRate / 100; return { base, discount, tax, total: base - discount + tax }; }
    function updateLiveTotals() { let subtotal = 0, discount = 0, tax = 0; tbody.querySelectorAll('tr').forEach(row => { const v = calcRow(row); subtotal += v.base; discount += v.discount; tax += v.tax; row.querySelector('.est-line-total').textContent = money(v.total); }); const headDiscountInput = form.querySelector('[name="discount"]'), headType = form.querySelector('[name="discount_type"]').value; let headDiscount = number(headDiscountInput.value); if (headType === 'percent') headDiscount = subtotal * headDiscount / 100; headDiscount = Math.min(Math.max(headDiscount, 0), Math.max(subtotal - discount, 0)); discount += headDiscount; form.querySelector('#live-subtotal').textContent = money(subtotal); form.querySelector('#live-discount').textContent = '- ' + money(discount); form.querySelector('#live-tax').textContent = money(tax); form.querySelector('#live-grand').textContent = money(subtotal - discount + tax); }

    document.querySelectorAll('[data-add-estimate-item]').forEach(button => button.addEventListener('click', () => { const type = button.dataset.addEstimateItem; if (type === 'other') addRow(type); else openCatalog(type, null); }));
    tbody.querySelectorAll('tr').forEach(row => { updateTrigger(row); const product = row.querySelector('.est-product-id')?.value; if (product) row.querySelector('.est-price').readOnly = !canOverridePrice; });
    tbody.addEventListener('click', e => { const remove = e.target.closest('.remove-estimate-row'); if (!remove) return; remove.closest('tr').remove(); emptyState.classList.toggle('d-none', tbody.querySelectorAll('tr').length > 0); updateLiveTotals(); });
    tbody.addEventListener('click', e => { const trigger = e.target.closest('.catalog-trigger'); if (!trigger) return; openCatalog(trigger.closest('tr').querySelector('.est-type').value === 'labor' ? 'labor' : 'part', trigger.closest('tr')); });
    tbody.addEventListener('input', e => { if (e.target.matches('.est-qty,.est-price,.est-disc,.est-tax')) updateLiveTotals(); });
    tbody.addEventListener('change', e => { if (e.target.matches('.est-disc-type,.est-type')) updateLiveTotals(); });
    form.querySelector('[name="discount"]').addEventListener('input', updateLiveTotals); form.querySelector('[name="discount_type"]').addEventListener('change', updateLiveTotals);
    searchInput.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => loadCatalog(true), 250); });
    loadMore.addEventListener('click', () => { page += 1; loadCatalog(false); });
    productFilters.querySelectorAll('button').forEach(button => button.addEventListener('click', () => { stockFilter = button.dataset.stock; productFilters.querySelectorAll('button').forEach(item => item.className = 'btn btn-sm btn-outline-secondary'); button.className = 'btn btn-sm btn-primary'; loadCatalog(true); }));
    results.addEventListener('click', e => { const result = e.target.closest('.catalog-result'); if (!result) return; selectCatalog(JSON.parse(result.dataset.catalog)); });
    updateLiveTotals();
})();
</script>
@endpush
