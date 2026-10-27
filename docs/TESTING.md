# TESTING — Bengkel Paten

- Runner: `php artisan test` (PHPUnit, sqlite `:memory:`, 474 tests: 473 passed, 1 skipped pra-ada).
- Kualitas: `vendor/bin/pint --test`, `vendor/bin/phpstan analyse` (level 5, 0 error).
- Cakupan: unit, feature, API, otorisasi, keamanan (IDOR/BOLA, 429, CORS,
  tampering), database (fresh+seed sqlite & MySQL), workflow E2E
  (`EndToEndWorkshopFlowTest`), akuntansi, inventory (race), POS, payment
  (idempotency + webhook), isolasi cabang.
- Aturan: jangan hapus test agar CI hijau; perbaiki → uji ulang → regresi penuh.
- CI: `.github/workflows/ci.yml` (PHP 8.3 + job MySQL, Pint, PHPStan,
  audit non-blocking, build frontend).
