# POS — Bengkel Paten

Otoritas: `PosService` (+ `SaleService`), sesi via `PosSession`
(`CloseStalePosSessions` menutup sesi basi).

## Alur

Buka sesi (kas awal) → penjualan (item, diskon, pajak) → checkout
(split payment didukung) → stok terpotong + jurnal + invoice/struk →
tahan (held) / lanjutkan → retur/refund (stok + jurnal koreksi) →
tutup sesi (rekonsiliasi kas) → cetak (thermal `escpos-php`, barcode).

## Jaminan

- Satu transaksi: sale → inventory → accounting konsisten (`PosCheckoutTest`).
- Checkout idempoten (`idempotency_key`) — retry aman, tak dobel tagih.
- Kasir dibatasi role (`kasir`); baca sesi untuk inventory/manager.
- API: `pos/open`, `pos/close`, `pos/checkout`; web POS untuk kasir counter.
