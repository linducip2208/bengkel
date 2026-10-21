# INVENTORY — Bengkel Paten

Otoritas stok: `StockService` (satu choke-point) + ledger
`StockRecord`/`StockHistory` + `WarehouseStock`. Kuantitas DECIMAL, anti-negatif.

## Alur

Produk (tipe, satuan, variasi, harga supplier) → gudang → supplier →
PO (`PurchaseOrderWorkflowService`: submit/approve/close) →
penerimaan (`PurchaseOrderReceiptService` → jurnal + stok) →
transfer/adjustment/reservasi → konsumsi bengkel/POS →
retur/rollback (stok dikembalikan + jurnal koreksi).

## Jaminan

- Transaksi + `lockForUpdate` di semua mutasi; stok awal tepat sekali.
- Reservasi parts untuk grup approved (`reservePartsForApprovedGroups`).
- Biaya: rata-rata/cost per penerimaan; COGS saat invoice.
- Race diuji: `InventoryConcurrencyTest`, `StockRollbackTest`,
  `ApprovedPartsReservationTest`, `EstimateDoesNotAffectStockTest`.

Menambah gudang/satuan/konversi: menu master data (web); mutasi stok
hanya lewat service — tidak ada update kuantitas langsung dari client.
