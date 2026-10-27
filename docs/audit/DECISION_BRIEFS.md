# DECISION BRIEFS — butuh keputusan produk (analisis kode aktual, tanpa perubahan logika)

## D1. Double-decrement stok lintas jalur (P0-7)

**Mekanisme aktual (terverifikasi di kode):**
- Jalur servis AMAN internal: `ProductService::useInService` (dipanggil `ServiceService:124` saat buat servis + `PartReservationController:96` saat approve reservasi, tipe `usage` ref Service) → `ServiceService::executeComplete` TIDAK decrement ulang, hanya mencatat dari `StockHistory` ke baris invoice. Idempoten + lock. (`ServiceService.php:345-360`)
- Jalur estimasi AMAN internal: `EstimateService::convertToInvoice` decrement tipe `out` ref Invoice (`EstimateService.php:869`), diguard 422 bila sudah `STATUS_CONVERTED` (`:672, :787`). Estimasi tidak menyentuh stok saat dibuat (`EstimateDoesNotCreateJournalTest`/`EstimateDoesNotAffectStockTest`).
- **Celah nyata: lintas jalur.** Tidak ada kunci dedup yang menghubungkan event `usage` (ref Service) dengan event `out` (ref Invoice) untuk part fisik yang sama — mis. part di-reservasi/dipakai untuk servis yang berasal dari estimasi, lalu estimasi itu dikonversi (decrement lagi). `reference_type/reference_id` tercatat tapi tidak bisa disimpulkan otomatis apakah dua pemakaian sah atau ganda.

**Opsi:**
- A. Single-event rule: konversi estimasi TIDAK decrement bila estimasi sudah terhubung ke servis yang mengonsumsi part (decrement hanya di `useInService`).
- B. Tautkan reservasi/usage ke `service_estimate_id`; konversi melewati item yang sudah ada event `usage`-nya.
- C. Terima sebagai dua konsumsi sah + SOP operasional (tanpa perubahan kode).

**Rekomendasi:** A atau B. Estimasi +/*: tidak ada yang aman dipilih sepihak karena menyentuh nilai persediaan.

**Status 2026-10-28: SENGAJA TIDAK di-auto-dedup.** De-duplikasi otomatis berisiko *under-count* diam-diam bila dua event ternyata konsumsi sah yang berbeda (part estimasi + part tambahan servis). Tanpa kunci bisnis yang compuesto (mis. estimate_id di usage events), kode tidak bisa membedakan "ganda" vs "dua pemakaian sah" — tebakan otomatis ke arah mana pun merusak satu skenario. Menunggu pilihan A/B/C di atas.

## D2. Void invoice ber-payment (P0-8)

**Koreksi temuan lama:** hapus invoice UNPAID ternyata SUDAH mengembalikan stok + reversal jurnal (`InvoiceService::deleteWithStockRestore`, `InvoiceController:destroy`). Klaim "tanpa jalur pembatalan" hanya benar untuk invoice BERBAYAR: `destroy` abort 403 bila ada payment (`InvoiceController:147`, `InvoiceService:285`).

**Opsi:**
- A. Fitur void: status `void` + increment stok + payment record refund + reversal jurnal, di-gate permission khusus (mis. `invoice.void`), full audit log.
- B. Credit-note sebagai pengganti void (tanpa ubah invoice asal).
- C. Pertahankan blokir + SOP manual di luar sistem.

**Rekomendasi:** A (sketsa: tiru `deleteWithStockRestore` + buat payment negatif + `reverseJournal*`, bukan hapus fisik).

**Status 2026-10-28: TERIMPLEMENTASI (opsi A).** `POST /invoices/{invoice}/void` (perm `invoice.void`): migrasi `voided_at`/`void_reason`, `InvoiceService::voidInvoice` (restore stok + reverse jurnal AR/HPP/payment + refund PaymentRecord negatif + Income offset + ActivityLog, riwayat dipertahankan), guard anti double-void (422), blokir bayar/hapus pada invoice void, badge Void di index/show, `InvoiceVoidTest` 4/4.

## D3. Stock-adjust langsung vs approval workflow (P1)

**Mekanisme aktual:** `products.stock-adjust` (perm `product.stock-adjust`, `ProductController:133-163`) langsung apply add/reduce/set. Terpisah, `stock-adjustments.*` (perm `inventory.adjust` + `stock-adjustment.approve`) adalah workflow pengajuan→approve. Keduanya fungsional, bukan bug — pertanyaannya governance.

**Opsi:**
- A. Pertahankan dua tier + dokumentasikan (koreksi cepat vs opname formal).
- B. Semua adjust lewat approval.
- C. Berbasis ambang: kecil langsung, besar butuh approve.

**Rekomendasi:** A atau C.

**Status 2026-10-28: opsi A didokumentasikan (tanpa perubahan kode).** `products.stock-adjust` (perm `product.stock-adjust`) = koreksi cepat langsung-apply oleh pemegang gudang; `stock-adjustments.*` (perm `inventory.adjust` + `stock-adjustment.approve`) = opname formal dua-tahap dengan approver terpisah. Keduanya tercatat di `StockHistory` + `ActivityLog`. Bila volume koreksi besar ingin dibatasi, lanjutkan ke opsi C (ambang nominal) — perlu keputusan angka ambang.

## D4. RequirePair API (verifikasi, bukan keputusan)

RequirePair global di grup `web` (`bootstrap/app.php:35`) + allowlist publik di `shouldBypass` — integrasi PENUH untuk web, teruji (`LicenseGateTest` 3/3). Grup `api` SENGAJA tanpa RequirePair (mobile Sanctum + endpoint publik booking/portal). Dinilai by-design; tidak diubah.
