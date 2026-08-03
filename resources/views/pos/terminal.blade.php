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
</style>
@endpush
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-cash-stack me-2"></i>POS Terminal
        <span class="badge bg-success ms-2">Sesi Buka — {{ $session->opened_at->format('d M H:i') }}</span>
    </h5>
    <div>
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
        </form>
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
            subtotal += it.price * it.quantity;
            const row = document.createElement('div');
            row.className = 'cart-item';
            row.innerHTML = `
                <div>
                    <div class="name">${it.name}</div>
                    <div class="meta small text-muted">${fmt(it.price)} × ${it.quantity}</div>
                    <input type="hidden" name="items[${id}][product_id]" value="${id}">
                    <input type="hidden" name="items[${id}][unit_price]" value="${it.price}">
                </div>
                <input type="number" name="items[${id}][quantity]" class="form-control form-control-sm qty-input" value="${it.quantity}" min="1" data-id="${id}">
                <div class="total">${fmt(it.price * it.quantity)}</div>
                <button type="button" class="btn btn-sm btn-outline-danger" data-remove="${id}"><i class="bi bi-x"></i></button>
            `;
            cartEl.appendChild(row);
        });
        const discount = parseFloat(discountInput.value || 0);
        const grand = Math.max(subtotal - discount, 0);
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
            cart[p.id] = { id: p.id, name: p.name, price: parseFloat(p.price), quantity: 1, stock: p.stock };
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

    render();
})();
</script>
@endsection
