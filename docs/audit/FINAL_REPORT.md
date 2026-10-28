# FINAL REPORT V11 — Bengkel Pro (2026-10-27, branch main)

## 1. VERDIK: BELUM SIAP (blocker jelas di bawah)
Alasan: (a) browser QA + cetak hardware belum dilakukan; (b) backup-restore belum di-drill; (c) P0-3/P0-4 butuh keputusan produk. Semua P0 lain yang ditemukan sudah diperbaiki + teruji.

## 2. SKOR: rata-rata 77.1/100 (PARTIAL semua fitur)
Detail per fitur di FEATURE_SCORECARD.md (metodologi: 25+20+20+15+10+5+5, UI dibatasi tanpa browser, P0/P1 terbuka membatasi skor). Tidak ada 100/100 — sesuai aturan.

## 3. TEMUAN DAN PERBAIKAN
P0 diperbaiki (9): duplikat const settings fatal; SSRF printer->allowlist+422; IDOR download->branch 404; sesi nonaktif->EnsureActiveUser+401; deadlock 6->5; advance tanpa invoice/lunas->guard + test; 2FA API->challenge OTP+verify+test; void invoice ber-payment->POST void + restore stok + reverse jurnal + refund + test; double-decrement lintas jalur->konversi hanya decrement sisa belum terpakai + test.
P0 terbuka: NIHIL di level kode (sisa gate rilis: browser QA, cetak hardware, backup drill).
P1 diperbaiki (~25 item, lihat FINDINGS.md): @can sidebar, quick action, notifikasi, aria, form terbuang + @error + modal reopen, opname transaksional, withRole anti-crash, N+1 laporan, eager load, prefix PB-, dead code sales, double-submit guard, over-refund test, settings sticky+tab, dsb.
P1 ditahan: portal brute-force, gate-pass pre-lunas (butuh SOP). Stock-approval governance didokumentasikan opsi A (D3); mass-assign dikunci test user+customer.

## 4. TEST: 492 tests - 491 passed, 1 skipped (pra-ada), 0 failed.
- Baru: SecurityHardeningTest(4), LicenseGateTest(3), CrudFormRegressionTest(6), ReportsAggregationTest(3), SellReturnGuardTest(2), SettingsPageTest(1), CompanySmokeTest(2), FleetContractSmokeTest(2), InsuranceClaimSmokeTest(3), TaxRateSmokeTest(2), PurchaseRequisitionSmokeTest(2), RefundAuthZTest(2), SchedulerSmokeTest(1), VoucherLoyaltySmokeTest(3), MarketingCommandsTest(6), InvoiceVoidTest(4), EstimateConvertStockDedupTest(3), PurchaseMenuVisibilityTest(2), ApiTwoFactorTest(6).
- Pint PASSED, PHPStan level 5 0 error, view:cache OK, migrate:fresh --seed OK (sqlite :memory:), routes 799, `composer audit` bersih (guzzle 8.3.1), `npm audit` 0 vuln, diff --check bersih.
- Tidak dijalankan: browser automation (tidak tersedia), MySQL lokal (CI), backup drill.

## 5. SECURITY & DATA
Server-side enforcement dipertahankan (middleware+gate+scope). Regression: SSRF 422, cross-branch 404, inactive 401, double-submit 1 invoice, over-refund ditolak. Batas: 2FA API, token expiry (ops), CSRF-except payment, CSP, portal auth, mass-assign latent. Transaksi finansial: lockForUpdate + idempotency + guard status; tanpa perubahan logika uang.

## 6. FILES & GIT
Ubah: app/Http/{Controllers(Api/Auth, Tenant/PrintController, MediaAttachmentController, BookingController, Purchase*Controller, ServiceController, ReportController, TechnicianSkill, RoleController, User withRole)}, app/Services/{ServiceService, ReportService, CustomerService, PosService? tidak}, app/Models/{Customer}, app/Http/Requests/CustomerRequest, database/migrations 2 file, bootstrap/app.php, config/services.php, composer.json (lint/stan), belasan views, docs (TESTING, REDESIGN-UI-UX ronde, audit/*), 6 file test baru; hapus: SaleController + 4 view sales.
Commit: main sinkron origin/main (bfce48e → … → bc31018, dst. `git log --oneline`).
Ronde lanjutan: fitur void invoice (migrasi voided_at/reason, InvoiceService::voidInvoice, reverse jurnal COGS/payment, route+perm invoice.void, badge Void), fix birthday:send (birth_date + whereMonth/whereDay), MarketingCommandsTest(6), InvoiceVoidTest(4).
Ronde ini: D1 double-decrement SELESAI (dedup sisa-belum-terpakai di convertToInvoice + EstimateConvertStockDedupTest 3/3), menu PO ikut perm purchase-order (PurchaseMenuVisibilityTest 2/2), guzzle 7.10.6 → 8.3.1 (9 advisory bersih, pemakaian langsung kompatibel).
Tidak ada secret/data produksi di diff (verifikasi: git status bersih kecuali file kerja; .env/bengkel_paten/log ter-ignore).

## 7. REMAINING WORK (prioritas)
1. Endpoint OTP 2FA API + test (P0) - SELESAI ronde ini.
2. Void invoice + double-decrement (P0 bisnis) - SELESAI ronde ini (D2 opsi A + D1 dedup sisa-belum-terpakai, teruji).
3. Browser QA + cetak hardware + screenshot (gate rilis).
4. Backup drill (butuh MySQL + eksekusi restore; perintah `backup:db` memakai mysqldump). RequirePair integrasi penuh - TERVERIFIKASI by-design (global web + allowlist, LicenseGateTest 3/3, lihat D4).
5. Test modul nol + marketing commands + notifikasi - SELESAI (Company/Fleet/insurance/TaxRate/PR/RefundAuthZ/VoucherLoyalty/MarketingCommands smoke). Nutup juga bug birthday:send (kolom tak-ada + MONTH() MySQL-only).
6. Mass-assign test khusus - SELESAI (user + customer privileged-fields). Stock-approval governance - opsi di DECISION_BRIEFS.md (D3), menunggu pilihan.
7. Purchases-vs-PO navigasi, settings save-per-tab kompleks, CSP.

## 8. PRODUKSI
Ikuti PRODUCTION_READINESS.md. Bedakan: kode siap-review, infrastruktur/validasi-prod BELUM (env, MySQL staging, backup drill, monitoring). Rollback: git revert + down() migrasi; jangan sentuh data prod.
