# BACKUP & RECOVERY — Bengkel Paten

## Perintah

- `php artisan backup:db --keep=14` → `storage/app/backups/backup-Ymd-His.sql`
  (mysqldump, retensi 14 file terbaru). Jadwalkan via scheduler (cron harian).
- Backup storage (foto/dokumen): sinkronkan `storage/app/public` + `.env`
  (sekuriti: simpan kredensial di luar repo) ke lokasi off-server.

## Restore

1. Buat DB kosong → `mysql < backup-*.sql`.
2. Salin kembali `storage/app/public` + `storage/app/license.lock` (pairing ulang
   bila host berubah) + `.env` yang benar.
3. `php artisan migrate:status` harus semua Ran; `php artisan test` hijau.

## Kebijakan

- Jangan edit DB produksi manual — semua perubahan via migrasi.
- Rollback migrasi: `php artisan migrate:rollback --step=N` (staging dulu).
- Uji restore berkala di DB terpisah (bukan produksi).
