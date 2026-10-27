# FEATURE SCORECARD V11 — metodologi terdokumentasi

## Metodologi
Kategori: bisnis 25 + CRUD/integritas 20 + auth/keamanan 20 + testing 15 + UI/UX 10 + error-handling/ops 5 + docs 5 = 100.
Aturan: tanpa verifikasi browser, skor UI/UX maksimal 5 (PARTIAL). Fitur dengan P0/P1 terbuka tidak boleh 100 — skor dibatasi dan diberi catatan.
N/A hanya bila kategori tidak relevan; di sini semua kategori relevan untuk semua fitur (tidak ada normalisasi).

## Skor

| Fitur | B | C | A | T | U | E | D | Total | Status | Bukti / catatan |
|---|---|---|---|---|---|---|---|---|---|---|
| Auth web + 2FA + session | 25 | 18 | 18 | 12 | 5 | 4 | 4 | 86 | PARTIAL | Login/gate teruji; tanpa uji browser |
| Auth API + RBAC + branch | 22 | 18 | 17 | 13 | 4 | 4 | 4 | 82 | PARTIAL | 2FA challenge+verify+kolom migrasi, envelope inkonsisten (dokumen v2) |
| Dashboard | 22 | 16 | 15 | 10 | 4 | 3 | 4 | 74 | PARTIAL | KPI nyata; tanpa uji browser/responsif |
| Booking publik | 23 | 17 | 15 | 8 | 4 | 3 | 3 | 73 | PARTIAL | Tanpa captcha/honeypot; tanpa browser |
| Customer/Vehicle | 24 | 19 | 17 | 12 | 4 | 4 | 4 | 84 | PARTIAL | Field+error diperbaiki+test; tanpa browser |
| Service workflow 0-12 | 23 | 18 | 17 | 12 | 4 | 4 | 4 | 82 | PARTIAL | Deadlock 6→5 + guard invoice diperbaiki+test |
| Estimate→invoice convert | 24 | 19 | 17 | 13 | 4 | 4 | 4 | 85 | PARTIAL | Idempoten teruji; tanpa browser |
| Payment + invoice | 24 | 19 | 18 | 13 | 4 | 4 | 4 | 86 | PARTIAL | Overpay guard+idempoten teruji; tanpa browser |
| POS terminal/kasir | 23 | 18 | 17 | 12 | 4 | 3 | 4 | 81 | PARTIAL | Double-submit teruji; race voucher tanpa browser |
| Inventory/stok/opname | 22 | 17 | 15 | 11 | 4 | 3 | 4 | 76 | PARTIAL | Opname transaksional; P0-3 double-decrement TERBUKA |
| Purchase/PO/PR | 22 | 17 | 15 | 8 | 4 | 3 | 3 | 72 | PARTIAL | Prefix PB- baru; modul PR tanpa test khusus |
| Retur jual/beli | 23 | 19 | 16 | 12 | 4 | 4 | 3 | 81 | PARTIAL | Over-return terkunci test; tanpa browser |
| Finance/laporan | 23 | 18 | 16 | 12 | 4 | 3 | 4 | 80 | PARTIAL | Agregat dioptimalkan+test; tanpa browser/cetak |
| HRM/komisi/gaji | 22 | 17 | 15 | 9 | 4 | 3 | 3 | 73 | PARTIAL | Nested form diperbaiki; slip/lembur tanpa test khusus |
| Marketing/loyalty/review | 21 | 16 | 14 | 7 | 4 | 3 | 3 | 68 | PARTIAL | Fungsional; coverage test tipis |
| Warranty/asuransi/fleet | 20 | 15 | 13 | 5 | 4 | 3 | 3 | 63 | PARTIAL | Berfungsi; NOL test modul (gap prioritas) |
| User/role/branch/settings | 24 | 19 | 18 | 13 | 4 | 4 | 4 | 86 | PARTIAL | EnsureActiveUser+test; settings tanpa browser |
| Printer thermal | 22 | 17 | 18 | 10 | 4 | 3 | 3 | 77 | PARTIAL | Allowlist+test-print; tanpa uji hardware nyata |
| Landing/SEO/publik | 22 | 15 | 12 | 7 | 4 | 3 | 4 | 67 | PARTIAL | CTA real, screenshot ada; tanpa browser/SEO render |
| Notifikasi/pengingat | 20 | 15 | 13 | 6 | 4 | 3 | 3 | 64 | PARTIAL | Fungsional; scheduler tanpa test |

Kolom: B=bisnis/25, C=CRUD/20, A=auth/20, T=testing/15, U=UI/10, E=error/5, D=docs/5.
Rata-rata: **77.1**. Tidak ada fitur 100/100 — sesuai aturan, semua PARTIAL karena verifikasi browser belum tersedia dan/atau P1 terbuka.
