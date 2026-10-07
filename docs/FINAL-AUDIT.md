# FINAL AUDIT — Bengkel Paten (Laravel backend + API untuk Flutter)

Tanggal: 2026-10-07. Basis: working tree sesi ini (bukan merge).
Prinsip: nilai dari evidence, bukan target.

## 1. Bukti test & quality gate (perintah yang benar-benar dijalankan)

| Check | Hasil |
|---|---|
| `php artisan test` | **424 tests, 423 pass, 1 skip, 0 fail** (assertions 1780) |
| `vendor/bin/phpstan analyse` (level 5) | **0 errors** |
| `vendor/bin/pint --test` | **pass** |
| `composer validate` | **valid** (lock disinkron via `composer update --lock`) |
| `npm run build` | **pass** (manifest + css/js terbangun) |
| `php artisan migrate:fresh --seed --force` (sqlite `:memory:`, setara CI) | **green** (PermissionSeeder 246 permissions/7 roles, BlogSeeder) |
| `migrate:fresh --seed` MySQL (DB terpisah `bengkel_audit_test`, lalu di-drop; produksi tak tersentuh) | **green** |
| `php artisan route:list` | **776 routes** total; **110** di `api/v1` |
| `composer audit` (sesudah update) | 49 → **9 advisories, 1 paket (`guzzlehttp/guzzle`, menunggu upstream 7.15.2/8.0.1)** |
| `npm ci --ignore-scripts` + `npm run build` | **green** (build statis terbangun) |
| `npm audit` (sesudah `npm audit fix`) | 6 → **2 critical tersisa, dev-only** (`shell-quote` via `concurrently`; tidak ikut ke produksi karena output build statis; tanpa `--force` agar tidak merusak tooling) |

Perubahan sesi ini: `composer.json` (proprietary + escpos `^5.0`),
`AuthController` (hapus registrasi terbuka), `SecurityHeaders` middleware (web+API),
`ApiWorkshopController` + 28 routes workshop/health/branches/notifications/device-tokens,
upload foto kendaraan & servis via API (MIME+size, nama acak, 404 lintas cabang,
file ikut terhapus) + perbaikan IDOR `deleteImage` web,
`ApiWorkshopContractTest` (9 tests), `composer update` (framework dkk),
`npm audit fix`, health check database+cache+queue, CI audit non-blocking,
`docs/flutter-api-contract.md`, `docs/ARCHITECTURE_DECISIONS.md`, `LICENSE`, dokumen ini.

## 2. Skor per domain

| Domain | Skor | Status | Evidence / catatan |
|---|---|---|---|
| Architecture | 88 | PASS | Service layer tunggal, ADR-1..6, Pint+PHPStan hijau |
| Database | 88 | PASS | 145 migrasi; fresh+seed hijau di sqlite **dan MySQL** (DB audit terpisah, produksi utuh) |
| Workshop workflow | 90 | PASS | E2E `EndToEndWorkshopFlowTest` + approval/QC/rework/reject/expiry tests hijau |
| Inventory | 88 | PASS | `InventoryConcurrencyTest`, `StockRollbackTest`, ledger via `StockService` |
| Accounting | 88 | PASS | `InvoiceEditAccountingTest`, `JournalBalanceTest`, `ReportingReconciliationTest` |
| POS | 85 | PASS | `PosCheckoutTest`, idempotency key, cash session |
| API / Flutter-ready | 88 | PASS* | 110 endpoint + kontrak + 9 contract tests (termasuk upload & device-token). *File privat ber-otorisasi penuh masih roadmap |
| Security | 88 | PASS* | Fail-closed branch + regression IDOR/BOLA, headers, tanpa register publik, IDOR deleteImage diperbaiki, upload tervalidasi. *9 advisory guzzle menunggu upstream |
| Testing | 92 | PASS | 424 tests (was 415), 1 skip lama |
| UI/UX (Tabler) | 75 | WARNING | Build hijau, aset lokal (tanpa CDN). Audit per-halaman/interaksi belum dilakukan |
| Performance | 75 | WARNING | Eager loading di controller API; belum ada load test / N+1 audit menyeluruh |
| Documentation | 85 | PASS | Kontrak Flutter, ADR, README/DEPLOYMENT/cicilan lama; kode = acuan bila konflik |
| Production config | 85 | PASS | Health `/up` + `/api/v1/health` (db+cache+queue), `RequirePair`, `.env` tidak dicommit, CI + audit non-blocking. Pastikan `APP_DEBUG=false`, queue/redis/mail per DEPLOYMENT.md |
| Commercial | 90 | PASS | `proprietary` konsisten (composer.json + README), License v3 pairing aktif |

Tidak ada skor 100: setiap WARNING di atas adalah pekerjaan lanjutan yang jujur,
bukan kegagalan rilis.

## 3. Keamanan — hasil simulasi attacker (ditolak dengan benar)

- Tanpa token → 401 di semua endpoint workshop/finansial (test).
- Token cabang B membaca data cabang A → 404/koleksi kosong (test).
- `branch_id` asing saat create/update → 403/`AuthorizationException` (trait guard).
- Transisi status ilegal (task completed → start) → 422 (test).
- Manipulasi harga/total dari client → diabaikan, dihitung ulang server.
- Registrasi publik via API → dihapus (tidak ada route).
- Webhook payment: verifikasi signature + anti-replay (`PaymentGatewayService::handleCallback`).
- Sisa: advisory guzzle (outbound hanya ke URL gateway terkonfigurasi + timeout;
  pantau rilis 7.15.2/8.0.1 lalu `composer update guzzlehttp/guzzle`).

## 4. Integritas data (tidak divergen)

Invoice total vs payment (`paid_amount` = Σ payment, test),
AR aging vs sisa tagihan (test), jurnal debit = kredit + unique guard,
edit invoice = realign AR/COGS (test), stok ledger + lock + anti-negatif (test).

## 5. Yang TIDAK dikerjakan sesi ini (eksplisit, bukan klaim)

1. File privat ber-otorisasi penuh (foto saat ini di disk `public` mengikuti konvensi web yang ada).
2. Audit visual per-halaman web dan load/performance testing.
3. Menyentuh database produksi (dilarang; validasi MySQL memakai DB audit terpisah yang sudah di-drop).
