# FEATURE INVENTORY — Bengkel Pro (dari kode, 2026-10-27)

## Modul & status implementasi (terverifikasi ada route+controller+view/test)

| Modul | Rute | Kunci bisnis | Status |
|---|---|---|---|
| Auth web (login/logout/2FA) | login, logout, 2fa.* | is_active gate, OTP email, throttle | Berfungsi |
| Auth API (login/logout/logout-all/me) | api/v1/* | Sanctum token, throttle, ClampPerPage | Berfungsi; 2FA BYPASS (P0 terbuka) |
| Dashboard + widget config | /, dashboard.config | KPI/Chart dari DB, auto-refresh 60 dtk | Berfungsi |
| Booking publik + kalender | public.booking, bookings.* | validasi, teknisi anti-crash | Berfungsi |
| Customer/portal | customer.* | sesi portal terpisah | Berfungsi |
| Vehicle + riwayat servis | vehicles.* | plat dinormalisasi | Berfungsi |
| Service/workflow 0-12 | services.* | advanceWorkflow + guard, 6→5 | Berfungsi |
| Estimasi + approval | estimates.* | idempoten, convert guard | Berfungsi |
| Findings/WP/tasks/QC | findings.*, work-* | idempoten, QC pass→resolve | Berfungsi |
| Jobcard + print | jobcards.* | thermal ESC/POS + browser | Berfungsi |
| Invoice + pembayaran | invoices.*, payments.* | idempoten, overpay guard | Berfungsi |
| POS terminal/sesi/receipt/hold | pos.* | idempotensi key, branch check | Berfungsi |
| Produk/stok/opname/adjust/transfer | products.*, stock-* | lock + guard approval (web) | Berfungsi; opname loop kini transaksional |
| Purchase/PO/PR | purchases.*, purchase-orders.*, purchase-requisitions.* | guard draft, lock, idempoten | Berfungsi; nomor PO vs pembelian kini beda prefix (PO-/PB-) |
| Retur jual/beli | sell-returns.*, purchases.return.* | guard over-return teruji | Berfungsi |
| Supplier/supplier-claim | suppliers.*, supplier-claims.* | CRUD + validasi | Berfungsi |
| Finance (income/expense/petty/bank/budget/COA/journal) | finance.*, incomes.*, expenses.*, dst | jurnal auto | Berfungsi |
| HRM (komisi/gaji/absen/cuti/skill) | commissions.*, hrm.*, technician-skills.* | nested form diperbaiki | Berfungsi |
| Marketing (voucher/loyalty/review/blog/campaign) | vouchers.*, loyalty.*, dst | validasi voucher | Berfungsi |
| Warranty/recall/asuransi/fleet/subkon | warranty-claims.*, recalls.*, insurance-claims.*, fleet-contracts.*, subcontractors.* | state machine dasar | Berfungsi; TANPA test (gap) |
| Report 19 jenis + PDF/Excel | reports.* | GROUP BY tunggal (baru) | Berfungsi |
| User/role/permission/API token | users.*, roles.* | Guard::users.manage, proteksi super_admin terakhir | Berfungsi |
| Branch/company/holiday/washbay/business-hour | branches.*, companies.*, dst | fail-closed branch scope | Berfungsi |
| Master data (tipe/unit/warna/dll) | puluhan resource can.module | CRUD standar | Berfungsi |
| Settings + backup | settings.*, backup-page | save sticky + ingat tab; BackupDatabase --keep=14 | Berfungsi; backup.test.php TIDAK ADA |
| Printer thermal + test-print | printers.*, print.* | allowlist anti-SSRF | Berfungsi |
| SEO/PSEO/blog publik/docs/sitemap | seo.*, blog.*, docs.* | catch-all PSEO | Berfungsi |
| Landing page | / | CTA real, screenshot lokal | Berfungsi (visual belum diverifikasi browser) |
| Tenant layout | layouts/tenant.blade.php | 0 extends | LEGACY tidak dipakai |
| Web sales CRUD (Tenant\SaleController + views/sales) | — (tanpa route) | — | DIHAPUS (orphan terkonfirmasi, API SaleService tetap ada) |

Total: 791 routes (tidak dihitung ulang per-route sesi ini; angka dari audit sebelumnya).
