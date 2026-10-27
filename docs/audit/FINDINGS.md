# FINDINGS V11 — diferensiasi SEBELUM vs SESUDAH (semua diverifikasi di kode)

## P0 — ditemukan & DIPERBAIKI dalam sesi ini
1. **SyntaxError fatal settings**: duplikat `const input` satu scope mematikan seluruh blok script settings (views/settings/index.blade.php:417+420). → Dihapus satu, regression test SettingsPageTest.
2. **SSRF printer**: `printer_ip/port` mentah dari request → socket TCP arbitrer (PrintController::resolvePrinter). → Allowlist printer aktif terdaftar, 422 jika tidak cocok + test.
3. **IDOR unduhan media**: download tanpa cek parent (MediaAttachmentController). → Branch-scope check via parent (404 lintas cabang) + test.
4. **Sesi nonaktif tetap berlaku**: token/sesi lama lolos per-request. → Middleware EnsureActiveUser (web+api) + test 401/redirect.
5. **Deadlock workflow 6 (Waiting Parts)**: advance +1 selalu gagal, tanpa jalan kembali. → advance dari 6 kembali ke 5 (sesuai WORKFLOW_TRANSITIONS) + test.
6. **Advance finansial tanpa dokumen**: 8→9 tanpa invoice, 9→10 tanpa pelunasan. → Guard invoice-ada + payment_status==2 + test.

## P0 — TERBUKTI tapi DITAHAN (butuh keputusan produk, bukan bugfix aman)
7. **Double-decrement stok**: `useInService` (saat buat servis) + `convertToInvoice` (saat konversi) keduanya decrement tanpa dedup silang; reference tercatat (Service vs Invoice) tapi tidak bisa dibedakan otomatis apakah pemakaian ganda atau dua pemakaian sah. Perlu aturan bisnis eksplisit.
8. **Tanpa void/cancel invoice ber-stok**: tidak ada jalur pembatalan yang mengembalikan decrement `convertToInvoice`; destroy diblokir bila ada payment. Perlu desain fitur void/refund-stok.
9. **2FA bypass via API**: login API terbit token tanpa OTP. Perlu endpoint challenge OTP mobile (keputusan produk) - DIPERBAIKI ronde ini: challenge OTP+tambah kolom+test.

## P1 — DIPERBAIKI
- Menu sidebar tanpa @can (Leave/Permission, Skill, Bank, Rekonsiliasi, Budget, Printer, Numbering) + quick action service/invoice pakai .create.
- Notifikasi `href="#"` → span; aria-label hamburger/search/bell/global; login link SSR; og:image; label error guest.
- Field mati customer city/postal (migrasi + validasi + fillable + test); vehicle notes→description + @error keys + tampilkan plat (5 file + test).
- Modal tambah user: old()/error/reopen (test).
- Stock opname loop → transaksi atomik + urutan deterministik.
- `User::withRole` anti-crash di 7 titik (booking publik 500 saat role hilang + test LicenseGate).
- N+1 laporan: batch lookup teknisi/produk + GROUP BY per cabang/bulan (test ReportsAggregationTest).
- Eager load workflow di index servis + withCount customer.
- Prefix nomor pembelian PB- vs PO purchase-order (cegah tabrakan tekstual).
- Hapus Tenant\SaleController + 4 view yatim (0 referensi, test 0).
- Double-submit: disable-on-submit payments/purchases/checkout + hint kurang-bayar POS.
- over-refund retur: guard ada → kunci dengan test.

## P1 — DITAHAN (keputusan produk / risiko)
- Stock-adjust API bypass approval (product.stock-adjust vs stock-adjustment.approve): dua fitur berbeda atau pelanggaran SoD? Perlu keputusan.
- Mass-assignment fillable luas: aman-terverifikasi (semua controller eksplisit/validated); penguncian via test anti-mass-assign? Belum ditambah — disarankan.
- Customer portal brute-force + password default: kebijakan kredensial portal.
- Gate Pass bisa dicetak sebelum lunas (by design? perlu SOP).
- Tanpa void invoice ber-payment (lihat P0-8).

## P2/P3 — DIPERBAIKI (pilihan aman)
Dark-mode scoping enterprise CSS; thead sticky absensi; filter lanjutan estimates (details); reset di 40+ filter GET; ikon alt/aria; thead sticky; localStorage try/catch (layout); SW hanya GET; kalender responsif + noscript; slip gaji (tombol cetak/kop/status/baris nol); ringkasan cetak invoice; composer lint/stan; docs count sync.

## P2/P3 — DITAHAN
CSP (berisiko matikan inline JS), N+1 kecil (technicianTimeReport, workPackageTimeReport, HrmController, stockReport agregat PHP), purchases vs PO duplikasi menu (navigasi), settings save-per-tab kompleks (cukup sticky+ingat tab), kalkulasi di view invoices (finansial), format tanggal campur di luar komisi, backup-restore drill, RequirePair integrasi penuh, duplikasi helper test.
