# BASELINE — Bengkel Pro V11 (terverifikasi 2026-10-27)

- Branch: `main` @ `4b5ab2d` (working tree BERSIH setelah commit; ahead 1 dari origin/main — BELUM di-push per aturan V11).
- Baseline awal sesi: `cd375df`, worktree bersih, origin/main sinkron.
- PHP 8.3, Laravel 13, Sanctum 4.3, spatie/laravel-permission 7.4, dompdf 3.1, escpos-php 5, maatwebsite/excel 3.1 (composer.json).
- Frontend: vite 8 + tailwind 4 (build only); ikon/Bootstrap/FA/jQuery/Chart.js via CDN di layout; `playwright` ada di devDeps tapi browser TIDAK tersedia.

## Baseline test (dijalankan, bukan asumsi)

- `php artisan test` (sqlite `:memory:`): 457 tests — 456 passed, 1 skipped, 0 failed.
- `vendor/bin/pint --test`: PASSED.
- `php vendor/bin/phpstan analyse` (level 5): 0 ERROR.
- `php artisan view:cache`: OK (375 blade compile).
- `npm run build`: OK (vite, manifest + css/js).
- `php artisan route:list`: OK (791 routes, tidak dihitung ulang per-route di sesi ini).
- `migrate:fresh --seed` (sqlite :memory:): OK termasuk 2 migrasi baru (index laporan, city/postal_code customer).
- Browser QA: TIDAK TERSEDIA — semua temuan visual bertanda UNVERIFIED.
- MySQL integration: tidak dijalankan lokal (butuh service MySQL 8.4 seperti CI); tercakup CI `tests-mysql`.
