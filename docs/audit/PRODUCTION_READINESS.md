# PRODUCTION READINESS V11 — VERDIK: BELUM SIAP (blocker tercatat)

## Status: BELUM SIAP produksi
Alasan: P0 terbuka (double-decrement policy, void-invoice policy), browser QA belum dilakukan, backup drill belum dilakukan.

## Checklist deploy
- [x] Full suite hijau lokal (452/451+1 skip), Pint + PHPStan hijau
- [x] Migrasi additive + rollback terdefinisi (`down()` ada di 2 migrasi baru)
- [x] `.env.example` dilengkapi (PRINTER_IP/PORT — ditambah sesi ini)
- [ ] Set di server: `SANCTUM_TOKEN_EXPIRATION` (mis. 57600), `SESSION_SECURE_COOKIE=true`, `APP_ENV=production`
- [ ] Uji `migrate:fresh --seed` di staging MySQL 8.4 (CI `tests-mysql` mencakupnya — pantau hasil CI)
- [ ] Backup drill: restore ke DB terpisah + catat hasil
- [ ] Browser QA: 1366x768, 1920x1080, tablet, mobile (landing, login, dashboard, POS, cetak)
- [ ] Uji cetak thermal ke hardware nyata + test-print
- [ ] Tutup/putuskan P0 bisnis: double-decrement policy, void-invoice policy; P1 stock-approval API
- [ ] Pantau CI GitHub Actions untuk commit ini (tests, frontend, tests-mysql)

## Rollback
- Kode: `git revert <commit>` (tanpa force-push; branch main, push biasa setelah review).
- Migrasi baru: `down()` tersedia (drop index, drop kolom city/postal_code).
- Data: JANGAN rollback DB produksi; seed hanya untuk testing/staging.

## Monitoring pasca-rilis (rekomendasi, belum terpasang)
- Log error + queue failed; alert backup harian; cek scheduler `schedule:list`.
