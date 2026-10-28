# TEST MATRIX V11 — hasil aktual per area (perintah persis)

| Area | Command | Hasil |
|---|---|---|
| Full suite | `php artisan test` (sqlite :memory:) | 500 tests — 499 passed, 1 skipped (pra-ada), 0 failed |
| Security regression | `php artisan test --filter="SecurityHardeningTest"` | 4/4 passed (inactive 401, SSRF 422, cross-branch 404, double-submit 1 invoice) |
| License gate | `php artisan test --filter="LicenseGateTest"` | 3/3 passed (unpaired→/__pair, publik lolos, paired→auth) |
| CRUD forms | `php artisan test --filter="CrudFormRegressionTest"` | 6/6 passed (city/postal, deskripsi, @error key, modal reopen, anti-mass-assign user+customer) |
| Reports aggregation | `php artisan test --filter="ReportsAggregationTest"` | 3/3 passed (branch GROUP BY, monthly identik, teknisi names) |
| Sell return guard | `php artisan test --filter="SellReturnGuardTest"` | 2/2 passed (tolak over-return, double-retur ditolak) |
| 2FA API | `php artisan test --filter="ApiTwoFactorTest"` | 6/6 passed (428 tanpa token, kode salah 401, challenge asing 401, kode benar terbit token) |
| Company | `php artisan test --filter="CompanySmokeTest"` | 2/2 passed (CRUD, gate permission) |
| Fleet | `php artisan test --filter="FleetContractSmokeTest"` | 2/2 passed (CRUD + attach kendaraan, gate) |
| Insurance | `php artisan test --filter="InsuranceClaimSmokeTest"` | 3/3 passed (lifecycle approve→bayar, reject, gate) |
| Tax | `php artisan test --filter="TaxRateSmokeTest"` | 2/2 passed (CRUD, gate master-data) |
| PR | `php artisan test --filter="PurchaseRequisitionSmokeTest"` | 2/2 passed (draft→submit→approve, guard hapus, gate) |
| Refund authZ | `php artisan test --filter="RefundAuthZTest"` | 2/2 passed (sale.view, purchase.view) |
| Scheduler | `php artisan test --filter="SchedulerSmokeTest"` | 1/1 passed (backup 02:00, birthday 09:00) |
| Voucher/loyalty | `php artisan test --filter="VoucherLoyaltySmokeTest"` | 3/3 passed (CRUD+validate kode+guard hapus, adjust poin, gate) |
| Marketing commands | `php artisan test --filter="MarketingCommandsTest"` | 6/6 passed (birthday dry, voucher ultah, reaktivasi, notif queue, reminder) |
| Invoice void | `php artisan test --filter="InvoiceVoidTest"` | 4/4 passed (unpaid restore+jurnal, paid refund+blokir, double-void 422, gate) |
| Estimate dedup | `php artisan test --filter="EstimateConvertStockDedupTest"` | 3/3 passed (skip terpakai, sisa parsial, penuh tanpa pemakaian) |
| Purchase menu | `php artisan test --filter="PurchaseMenuVisibilityTest"` | 2/2 passed (link PO ikut perm PO, bukan purchase.view) |
| Customer portal | `php artisan test --filter="CustomerPortalTest"` | 6/6 passed (login nomor ternormalisasi, throttle 429, isolasi invoice, guard sesi) |
| Time reports | `php artisan test --filter="TimeReportTest"` | 2/2 passed (total benar + batas query anti N+1) |
| Deps | `composer audit` + `npm audit --omit=dev` | bersih (guzzle 8.3.1), 0 vuln |
| Settings page | `php artisan test --filter="SettingsPageTest"` | 1/1 passed (const tunggal + tab key) |
| AuthZ spot | `php artisan test --filter="AuthorizationTest"` | 16/16 passed |
| POS spot | `php artisan test --filter="PosCheckoutTest"` | 3/3 passed |
| E2E workshop | `php artisan test --filter="EndToEndWorkshopFlowTest"` | 1/1 passed (28 assertions) |
| Estimate+POS spot | `php artisan test --filter="EstimateHttpFlowSmokeTest\|PosCheckoutTest"` | 4/4 passed |
| Lint | `vendor/bin/pint --test` | PASSED |
| Static analysis | `php vendor/bin/phpstan analyse --no-progress --memory-limit=2G` | 0 errors (level 5) |
| Views compile | `php artisan view:cache` | OK (375 blade) |
| Frontend build | `npm run build` | OK |
| Routes | `php artisan route:list` | OK (799) |
| Migration | `migrate:fresh --seed` (sqlite :memory:) | OK |
| Diff hygiene | `git diff --check` | bersih |
| Browser (landing/login/dashboard/CRUD/responsif/cetak) | N/A — tanpa automation | BELUM DIVERIFIKASI |
| MySQL integration | CI `tests-mysql` | BELUM dijalankan lokal (butuh MySQL 8.4) |
| Backup restore drill | — | BELUM dilakukan |
| Coverage report | tidak ada tool coverage | N/A (tidak diklaim) |

Skip 1 = skip pra-ada (tidak ditambah, tidak dihapus). Tidak ada failure yang disembunyikan.
