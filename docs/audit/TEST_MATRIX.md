# TEST MATRIX V11 — hasil aktual per area (perintah persis)

| Area | Command | Hasil |
|---|---|---|
| Full suite | `php artisan test` (sqlite :memory:) | 457 tests — 456 passed, 1 skipped (pra-ada), 0 failed |
| Security regression | `php artisan test --filter="SecurityHardeningTest"` | 4/4 passed (inactive 401, SSRF 422, cross-branch 404, double-submit 1 invoice) |
| License gate | `php artisan test --filter="LicenseGateTest"` | 3/3 passed (unpaired→/__pair, publik lolos, paired→auth) |
| CRUD forms | `php artisan test --filter="CrudFormRegressionTest"` | 4/4 passed (city/postal, deskripsi, @error key, modal reopen) |
| Reports aggregation | `php artisan test --filter="ReportsAggregationTest"` | 3/3 passed (branch GROUP BY, monthly identik, teknisi names) |
| Sell return guard | `php artisan test --filter="SellReturnGuardTest"` | 2/2 passed (tolak over-return, double-retur ditolak) |
| 2FA API | `php artisan test --filter="ApiTwoFactorTest"` | 5/5 passed (428 tanpa token, kode salah 401, challenge asing 401) |
| Settings page | `php artisan test --filter="SettingsPageTest"` | 1/1 passed (const tunggal + tab key) |
| AuthZ spot | `php artisan test --filter="AuthorizationTest"` | 16/16 passed |
| POS spot | `php artisan test --filter="PosCheckoutTest"` | 3/3 passed |
| E2E workshop | `php artisan test --filter="EndToEndWorkshopFlowTest"` | 1/1 passed (28 assertions) |
| Estimate+POS spot | `php artisan test --filter="EstimateHttpFlowSmokeTest\|PosCheckoutTest"` | 4/4 passed |
| Lint | `vendor/bin/pint --test` | PASSED |
| Static analysis | `php vendor/bin/phpstan analyse --no-progress --memory-limit=2G` | 0 errors (level 5) |
| Views compile | `php artisan view:cache` | OK (375 blade) |
| Frontend build | `npm run build` | OK |
| Routes | `php artisan route:list` | OK (791) |
| Migration | `migrate:fresh --seed` (sqlite :memory:) | OK |
| Diff hygiene | `git diff --check` | bersih |
| Browser (landing/login/dashboard/CRUD/responsif/cetak) | N/A — tanpa automation | BELUM DIVERIFIKASI |
| MySQL integration | CI `tests-mysql` | BELUM dijalankan lokal (butuh MySQL 8.4) |
| Backup restore drill | — | BELUM dilakukan |
| Coverage report | tidak ada tool coverage | N/A (tidak diklaim) |

Skip 1 = skip pra-ada (tidak ditambah, tidak dihapus). Tidak ada failure yang disembunyikan.
