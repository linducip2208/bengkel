@extends('layouts.app')
@section('title', 'POS Terminal')
@push('styles')
<style>
    .pos-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1rem; height: calc(100vh - 8rem); }
    .pos-products { background: #fff; border-radius: 10px; padding: 1rem; overflow-y: auto; border: 1px solid #e5e7eb; }
    .pos-cart { background: #fff; border-radius: 10px; padding: 1rem; display: flex; flex-direction: column; border: 1px solid #e5e7eb; }
    .pos-cart .items { flex: 1; overflow-y: auto; }
    .product-card { display: grid; grid-template-columns: 1fr auto; gap: 0.5rem; padding: 0.65rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 0.4rem; cursor: pointer; transition: all 0.15s; }
    .product-card:hover { border-color: #3b82f6; background: #eff6ff; transform: translateX(2px); }
    .product-card .name { font-weight: 600; font-size: 0.92rem; }
    .product-card .meta { font-size: 0.78rem; color: #6b7280; }
    .product-card .price { font-weight: 700; color: #059669; }
    .product-card .stock { font-size: 0.72rem; color: #6b7280; }
    .product-card.out { opacity: 0.5; cursor: not-allowed; }
    .cart-item { display: grid; grid-template-columns: 1fr auto auto auto; gap: 0.4rem; align-items: center; padding: 0.5rem; border-bottom: 1px dashed #e5e7eb; }
    .cart-item .name { font-size: 0.88rem; font-weight: 500; }
    .cart-item .qty-input { width: 60px; text-align: center; }
    .cart-item .total { font-weight: 700; min-width: 90px; text-align: right; }
    .cart-summary { background: #f3f4f6; border-radius: 8px; padding: 1rem; margin-top: 0.75rem; }
    .cart-summary .row-line { display: flex; justify-content: space-between; padding: 0.25rem 0; }
    .cart-summary .grand { font-size: 1.4rem; font-weight: 800; color: #1e40af; padding-top: 0.5rem; border-top: 1px solid #d1d5db; }
    .pos-search { font-size: 1.05rem; padding: 0.75rem; }
    .keypad-btn { width: 100%; padding: 0.5rem; font-weight: 600; }
    @media (max-width: 768px) {
        .pos-grid { grid-template-columns: 1fr; height: auto; }
        .pos-products { max-height: 50vh; }
    }
</style>
@endpush
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-cash-stack me-2"></i>POS Terminal
        <span class="badge bg-success ms-2">Sesi Buka — {{ $session->opened_at->format('d M H:i') }}</span>
    </h5>
    <div>
        <button type="button" class="btn btn-outline-primary" id="recallBtn">
            <i class="bi bi-clock-history me-1"></i>Recall
        </button>
        <a href="{{ route('pos.closeForm', $session) }}" class="btn btn-warning">
            <i class="bi bi-lock me-1"></i>Tutup Sesi
        </a>
    </div>
</div>

<div class="pos-grid">
    <div class="pos-products">
        <input type="text" id="searchInput" class="form-control pos-search mb-3" placeholder="Scan barcode / ketik kode / nama produk..." autofocus>
        <div id="productList">
            @foreach($products as $p)
                @php $stock = $p->stockRecord?->quantity ?? 0; @endphp
                <div class="product-card {{ $stock <= 0 ? 'out' : '' }}" data-id="{{ $p->id }}" data-name="{{ e($p->name) }}" data-code="{{ e($p->code) }}" data-barcode="{{ e($p->barcode) }}" data-price="{{ $p->price }}" data-stock="{{ $stock }}">
                    <div>
                        <div class="name">{{ $p->name }}</div>
                        <div class="meta"><code>{{ $p->code }}</code> • Stok: <strong>{{ $stock }}</strong></div>
                    </div>
                    <div class="text-end">
                        <div class="price">@money($p->price)</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="pos-cart">
        <h6 class="mb-2"><i class="bi bi-cart me-1"></i>Keranjang Belanja</h6>
        <form action="{{ route('pos.checkout') }}" method="POST" id="checkoutForm">
            @csrf
            <input type="hidden" name="session_id" value="{{ $session->id }}">
            <input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::uuid() }}">

            <div class="mb-2">
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">Walk-in Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="items" id="cartItems">
                <div class="text-center text-muted py-4 small" id="cartEmpty">Klik produk untuk tambah ke keranjang</div>
            </div>

            <div class="cart-summary">
                <div class="row-line"><span>Subtotal</span><span id="sumSubtotal">Rp 0</span></div>
                <div class="row-line">
                    <span>Diskon</span>
                    <span><input type="number" name="discount" id="discountInput" class="form-control form-control-sm text-end" style="width: 110px;" value="0" min="0"></span>
                </div>
                <div class="row-line">
                    <span>Voucher</span>
                    <span style="display:flex;gap:4px;">
                        <input type="text" id="voucherCode" class="form-control form-control-sm" style="width: 110px;" placeholder="Kode voucher" autocomplete="off">
                        <input type="hidden" name="voucher_id" id="voucherId" value="">
                        <input type="hidden" name="voucher_discount" id="voucherDiscount" value="0">
                        <button type="button" id="applyVoucherBtn" class="btn btn-sm btn-outline-primary">Pakai</button>
                    </span>
                </div>
                <div class="row-line text-success" id="voucherInfo" style="display:none;font-size:0.78rem;"></div>
                <div class="row-line grand"><span>Total Bayar</span><span id="sumGrand">Rp 0</span></div>
                <div class="row-line mt-2">
                    <span>Metode Bayar</span>
                    <select name="payment_method_id" class="form-select form-select-sm" style="width: 160px;" required>
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->payment }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row-line">
                    <span>Uang Bayar</span>
                    <input type="number" name="amount_paid" id="amountPaid" class="form-control form-control-sm text-end" style="width: 140px;" value="0" min="0" required>
                </div>
                <div class="row-line"><span>Kembalian</span><strong id="changeAmount" class="text-success">Rp 0</strong></div>
            </div>

            <button type="submit" class="btn btn-success btn-lg w-100 mt-2" id="payBtn" disabled>
                <i class="bi bi-check2-circle me-1"></i>Bayar & Cetak Struk
            </button>
            <button type="button" class="btn btn-outline-warning w-100 mt-1" id="holdBtn">
                <i class="bi bi-pause-circle me-1"></i>Tahan (Hold)
            </button>
        </form>
    </div>
</div>

{{-- Modal Recall Transaksi Ditahan --}}
<div class="modal fade" id="heldModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-clock-history me-1"></i>Transaksi Ditahan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="heldList" class="list-group list-group-flush">
                    <div class="text-center text-muted py-3 small">Memuat...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const cart = {}; // id → {id, name, price, quantity}
    const items = document.querySelectorAll('.product-card');
    const cartEl = document.getElementById('cartItems');
    const empty = document.getElementById('cartEmpty');
    const discountInput = document.getElementById('discountInput');
    const amountPaidInput = document.getElementById('amountPaid');
    const payBtn = document.getElementById('payBtn');
    const searchInput = document.getElementById('searchInput');

    function fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

    function render() {
        cartEl.innerHTML = '';
        let subtotal = 0;
        const keys = Object.keys(cart);
        if (keys.length === 0) { cartEl.appendChild(empty); }
        keys.forEach(id => {
            const it = cart[id];
            const lineDisc = it.discount || 0;
            subtotal += it.price * it.quantity - lineDisc;
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML = `
                <div>
                    <div class="name">${it.name}</div>
                    <div class="meta small text-muted">${fmt(it.price)} × ${it.quantity}</div>
                    <input type="hidden" name="items[${id}][product_id]" value="${id}">
                    <input type="hidden" name="items[${id}][unit_price]" value="${it.price}">
                    <input type="hidden" name="items[${id}][discount_type]" value="fixed">
                    <input type="number" name="items[${id}][discount]" class="form-control form-control-sm discount-input" style="width:90px;" value="${lineDisc}" min="0" data-id="${id}" placeholder="Diskon">
                </div>
                <input type="number" name="items[${id}][quantity]" class="form-control form-control-sm qty-input" value="${it.quantity}" min="1" data-id="${id}">
                <div class="total">${fmt(it.price * it.quantity - lineDisc)}</div>
                <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${id}"><i class="bi bi-x"></i></button>
            `;
            cartEl.appendChild(row);
        });
        const discount = parseFloat(discountInput.value || 0);
        const vd = parseFloat(voucherDiscount.value || 0);
        const grand = Math.max(subtotal - discount - vd, 0);
        document.getElementById('sumSubtotal').textContent = fmt(subtotal);
        document.getElementById('sumGrand').textContent = fmt(grand);

        // Non-cash payment (Transfer/QRIS/Debit/Kredit/dst) → auto-fill paid = grand, sembunyikan kembalian
        const paymentSel = document.querySelector('select[name="payment_method_id"]');
        const paymentName = (paymentSel?.options[paymentSel.selectedIndex]?.text || '').toLowerCase();
        const isCash = paymentName.includes('cash') || paymentName.includes('tunai');

        if (!isCash && grand > 0) {
            amountPaidInput.value = grand;
            amountPaidInput.readOnly = true;
            amountPaidInput.classList.add('bg-light');
        } else {
            amountPaidInput.readOnly = false;
            amountPaidInput.classList.remove('bg-light');
        }

        const paid = parseFloat(amountPaidInput.value || 0);
        const change = Math.max(paid - grand, 0);
        document.getElementById('changeAmount').textContent = isCash ? fmt(change) : fmt(0);
        payBtn.disabled = !(keys.length > 0 && paid >= grand && grand > 0);
    }

    function addToCart(p) {
        if (p.stock <= 0) return;
        if (cart[p.id]) {
            if (cart[p.id].quantity + 1 > p.stock) { alert('Stok tidak cukup'); return; }
            cart[p.id].quantity += 1;
        } else {
            cart[p.id] = { id: p.id, name: p.name, price: parseFloat(p.price), quantity: 1, stock: p.stock, discount: 0 };
        }
        render();
    }

    items.forEach(el => {
        el.addEventListener('click', () => {
            addToCart({
                id: el.dataset.id,
                name: el.dataset.name,
                price: el.dataset.price,
                stock: parseInt(el.dataset.stock, 10),
            });
        });
    });

    cartEl.addEventListener('change', (e) => {
        if (e.target.matches('.qty-input')) {
            const id = e.target.dataset.id;
            cart[id].quantity = Math.max(1, parseInt(e.target.value, 10) || 1);
            render();
        } else if (e.target.matches('.discount-input')) {
            const id = e.target.dataset.id;
            cart[id].discount = Math.max(0, parseFloat(e.target.value) || 0);
            render();
        }
    });
    cartEl.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-remove]');
        if (btn) {
            delete cart[btn.dataset.remove];
            render();
        }
    });
    discountInput.addEventListener('input', render);
    amountPaidInput.addEventListener('input', render);
    document.querySelector('select[name="payment_method_id"]')?.addEventListener('change', render);

    // Search filter
    searchInput.addEventListener('input', () => {
        const q = searchInput.value.trim().toLowerCase();
        items.forEach(el => {
            const match = !q || el.dataset.name.toLowerCase().includes(q) || el.dataset.code.toLowerCase().includes(q) || (el.dataset.barcode || '').toLowerCase().includes(q);
            el.style.display = match ? '' : 'none';
        });
    });

    // Enter pada search → kalau hanya 1 hasil, auto-add
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const visible = Array.from(items).filter(i => i.style.display !== 'none' && !i.classList.contains('out'));
            if (visible.length === 1) {
                visible[0].click();
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            }
        }
    });

    // Voucher AJAX validation
    const voucherCode = document.getElementById('voucherCode');
    const voucherId = document.getElementById('voucherId');
    const voucherDiscount = document.getElementById('voucherDiscount');
    const voucherInfo = document.getElementById('voucherInfo');
    const applyVoucherBtn = document.getElementById('applyVoucherBtn');

    function applyVoucher() {
        const code = voucherCode.value.trim();
        if (!code) { voucherInfo.style.display = 'none'; voucherId.value = ''; voucherDiscount.value = '0'; render(); return; }
        const subtotal = Object.keys(cart).reduce((s, id) => s + cart[id].price * cart[id].quantity - (cart[id].discount || 0), 0);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('{{ route("vouchers.validate") }}', {
            method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify({code, subtotal})
        }).then(r => r.json()).then(d => {
            if (d.ok) {
                voucherId.value = d.voucher_id;
                voucherDiscount.value = d.discount;
                voucherInfo.textContent = 'Voucher ' + d.name + ': -' + fmt(d.discount);
                voucherInfo.style.display = '';
                voucherInfo.style.color = '#059669';
            } else {
                voucherId.value = ''; voucherDiscount.value = '0';
                voucherInfo.textContent = d.error;
                voucherInfo.style.display = '';
                voucherInfo.style.color = 'red';
            }
            render();
        }).catch(() => {});
    }
    applyVoucherBtn.addEventListener('click', applyVoucher);
    voucherCode.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); applyVoucher(); } });

    // Re-price product cards based on customer's selling price group
    const customerSelect = document.querySelector('select[name="customer_id"]');
    customerSelect?.addEventListener('change', async () => {
        const cid = customerSelect.value;
        try {
            const res = await fetch('{{ route("pos.prices") }}?customer_id=' + encodeURIComponent(cid));
            const data = await res.json();
            items.forEach(el => {
                const newPrice = data[el.dataset.id];
                if (newPrice !== undefined) {
                    el.dataset.price = newPrice;
                    const priceEl = el.querySelector('.price');
                    if (priceEl) priceEl.textContent = fmt(newPrice);
                }
            });
        } catch (e) {}
    });

    // --- Hold / Recall ---
    const holdBtn = document.getElementById('holdBtn');
    const recallBtn = document.getElementById('recallBtn');
    const heldModalEl = document.getElementById('heldModal');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const recallBase = '{{ route("pos.recall", ["held" => "__ID__"]) }}';
    const releaseBase = '{{ route("pos.release", ["held" => "__ID__"]) }}';

    function cartToPayload() {
        return Object.keys(cart).map(id => ({
            product_id: cart[id].id,
            name: cart[id].name,
            quantity: cart[id].quantity,
            unit_price: cart[id].price,
            discount: cart[id].discount || 0,
            discount_type: 'fixed',
        }));
    }

    holdBtn?.addEventListener('click', () => {
        const payload = cartToPayload();
        if (payload.length === 0) { alert('Keranjang kosong.'); return; }
        holdBtn.disabled = true;
        fetch('{{ route("pos.hold") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: JSON.stringify({
                session_id: '{{ $session->id }}',
                customer_id: customerSelect?.value || null,
                items: payload,
                discount: discountInput.value || 0,
            }),
        }).then(r => r.json()).then(d => {
            if (d.ok) {
                Object.keys(cart).forEach(id => delete cart[id]);
                discountInput.value = 0;
                voucherCode.value = ''; voucherId.value = ''; voucherDiscount.value = '0'; voucherInfo.style.display = 'none';
                amountPaidInput.value = 0;
                render();
                alert(d.message || 'Transaksi ditahan.');
            } else {
                alert('Gagal menahan transaksi.');
            }
        }).catch(() => alert('Gagal menahan transaksi.'))
          .finally(() => { holdBtn.disabled = false; });
    });

    function loadHeldList() {
        const listEl = document.getElementById('heldList');
        fetch('{{ route("pos.held") }}')
            .then(r => r.json())
            .then(d => {
                const helds = d.helds || [];
                if (helds.length === 0) {
                    listEl.innerHTML = '<div class="text-center text-muted py-3 small">Tidak ada transaksi ditahan.</div>';
                    return;
                }
                listEl.innerHTML = '';
                helds.forEach(h => {
                    const el = document.createElement('div');
                    el.className = 'list-group-item d-flex justify-content-between align-items-center';
                    el.innerHTML = `
                        <div>
                            <strong>${h.customer}</strong>
                            <div class="small text-muted">${h.items_count} item • Diskon ${fmt(h.discount)}</div>
                            <div class="small text-muted">${h.created_at}${h.notes ? ' • ' + h.notes : ''}</div>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" data-recall="${h.id}">Recall</button>
                            <button class="btn btn-sm btn-outline-danger" data-release="${h.id}"><i class="bi bi-trash"></i></button>
                        </div>`;
                    listEl.appendChild(el);
                });
            })
            .catch(() => { listEl.innerHTML = '<div class="text-center text-muted py-3 small">Gagal memuat.</div>'; });
    }

    recallBtn?.addEventListener('click', () => {
        loadHeldList();
        bootstrap.Modal.getOrCreateInstance(heldModalEl).show();
    });

    document.getElementById('heldList').addEventListener('click', (e) => {
        const recallId = e.target.closest('[data-recall]')?.dataset.recall;
        const releaseId = e.target.closest('[data-release]')?.dataset.release;
        if (recallId) {
            fetch(recallBase.replace('__ID__', recallId))
                .then(r => r.json())
                .then(d => {
                    const held = d.held;
                    held.items.forEach(it => {
                        cart[it.product_id] = {
                            id: it.product_id,
                            name: it.name || ('Produk #' + it.product_id),
                            price: parseFloat(it.unit_price),
                            quantity: parseInt(it.quantity, 10),
                            stock: 999999,
                            discount: parseFloat(it.discount || 0),
                        };
                    });
                    discountInput.value = held.discount || 0;
                    if (held.customer_id && customerSelect) customerSelect.value = held.customer_id;
                    render();
                    bootstrap.Modal.getOrCreateInstance(heldModalEl).hide();
                });
        } else if (releaseId) {
            if (!confirm('Hapus transaksi ditahan ini?')) return;
            fetch(releaseBase.replace('__ID__', releaseId), {
                method: 'DELETE',
                headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            }).then(() => loadHeldList());
        }
    });

    // Update render() to include voucher discount in grand total
    render();
})();
</script>
@endsection
