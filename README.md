# Bengkel Paten — Workshop ERP

Aplikasi manajemen bengkel end-to-end: **Customer → Vehicle → Booking → Check-in → Inspection → Job Card → Diagnosis → Estimate → Approval → Mechanic → Labor → Parts Reservation → Consumption → Inventory → QC → Completion → Invoice → Payment → Cash/Bank → Journal → Reports → Service History → Warranty → Next-Service Reminder**. 44 modul, multi-cabang, POS, booking online.

## Requirements

| Komponen | Versi |
|----------|-------|
| PHP | ^8.3 (ext: mbstring, pdo_mysql/sqlite, gd, zip) |
| Composer | ^2 |
| Node.js / npm | ≥ 20 / ≥ 10 |
| Database | MySQL 8 (produksi) atau SQLite (testing) |

## Instalasi

```bash
git clone <repo> bengkel && cd bengkel
composer install
cp .env.example .env          # lalu sesuaikan DB_*, APP_URL, dsb.
php artisan key:generate
php artisan migrate           # skema lengkap, aman dijalankan bertahap
php artisan db:seed --force   # demo data + PermissionSeeder (roles)
npm install --ignore-scripts
npm run build                 # Vite → public/build
php artisan serve             # http://127.0.0.1:8000
```

> Produksi real: cukup `php artisan db:seed --class=PermissionSeeder --force` untuk roles/permissions. `DatabaseSeeder` penuh = data demo.

## Environment Variables Penting

| Key | Default | Keterangan |
|-----|---------|------------|
| `APP_DEBUG` | `false` | WAJIB `false` di produksi |
| `DB_CONNECTION` / `DB_DATABASE` | mysql | sqlite `:memory:` hanya testing |
| `QUEUE_CONNECTION` | `database` | worker via supervisor (`deploy/supervisor.conf`) |
| `SESSION_DRIVER` | database | gunakan `database` di produksi |
| `STOCK_ALLOW_NEGATIVE` | `false` | izinkan stok minus (default: ditolak) |
| `LICENSE_DEV_BYPASS` | true | pairing lisensi whitelabel; matikan saat produksi |

## Migrations & Seeders

- Semua migration bersifat aditif; tidak ada `migrate:fresh` yang aman di produksi (hapus data).
- Migration integritas (`2026_08_26_*`) menambahkan: tabel `document_sequences`, UNIQUE index semua nomor dokumen, `journal_entries.entry_type`, idempotency key invoice/payment, dan index performa. Duplikat legacy di-rename (`-D2`, `-D3`) — tidak pernah dihapus.
- Seeder: `PermissionSeeder` (7 roles), `BlogSeeder`, `DatabaseSeeder` (demo).

## Queue & Scheduler

```bash
# Worker (supervisor: deploy/supervisor.conf)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# Scheduler (crontab: * * * * * php artisan schedule:run)
# Lihat routes/console.php — 12 jadwal: backup DB, reminder servis,
# escalate invoice overdue, notifikasi queue, loyalty birthday, dll.
```

## Test Commands

```bash
composer test                    # config:clear + php artisan test (SQLite :memory:)
vendor/bin/pint --test           # code style
vendor/bin/phpstan analyse       # static analysis level 5 (baseline: phpstan-baseline.neon)
php artisan migrate --env=testing  # verifikasi migrasi bersih
npm run build                    # frontend production build
```

CI (`.github/workflows/ci.yml`): composer install → migrate bersih → Pint → PHPStan → php artisan test → npm ci → npm run build. Error PHPStan di luar baseline = CI gagal.

## Multi-Cabang & Isolasi Data

- `BranchScope` memfilter query per cabang aktif (session web).
- **Assignment cabang**: tabel `branch_user`. User dengan assignment hanya bisa switch ke & melihat data cabangnya; tanpa assignment = akses global (kompatibel data lama); super_admin/admin selalu bebas.
- **API Sanctum** ikut ter-scope: token tanpa session otomatis dibatasi ke cabang user.

## Keamanan Upload

Bukti pembayaran customer (`payment-proofs/`) dan dokumen attachment (`attachments/`) disimpan di **disk private** — tidak bisa diakses anonim via `/storage`. Download lewat route ter-autentikasi (`invoices.payment-proof` untuk role admin+, `attachments.download` untuk staff login).

## Roles & Hak Akses

| Role | Cakupan |
|------|---------|
| super_admin | Semua akses + manajemen role/permission |
| admin | Semua operasional & keuangan |
| manager | Transaksi + approval adjustment + laporan keuangan |
| kasir | POS, sales, invoice & pembayaran |
| mekanik | Job card, QC, part usage |
| service_advisor | Booking, check-in, estimasi, komunikasi customer |
| inventory | Produk, stok, opname, penerimaan barang |

Enforcement server-side via middleware `role:` pada route sensitif (user/role management, approval stock adjustment, export laporan keuangan, API RBAC). Menu hanyalah tampilan.

## Alur Bisnis Utama

1. Customer & kendaraan terdaftar sekali — booking → job card tanpa input ulang.
2. Konsumsi part tercatat di ledger `stock_histories` (immutable) dengan lock baris (`StockService`).
3. Penyelesaian servis idempoten: double-submit tidak menciptakan invoice kedua (`invoices.service_id` UNIQUE).
4. Invoice otomatis posting jurnal akrual: Dr Piutang / Cr Pendapatan Jasa+Parts (+ Dr HPP / Cr Persediaan).
5. Pembayaran terkunci per-invoice (overpayment & double-click mustahil), idempotency-key opsional.
6. Setiap jurnal wajib seimbang — `AutoJournalService` menolak entri timpang.
7. Retur/void membalik stok & jurnal tanpa menghapus riwayat audit.

## Deployment Produksi

Lihat [DEPLOYMENT.md](DEPLOYMENT.md) — termasuk langkah update (`git pull` → `migrate --force` → optimize → restart supervisor), Nginx config (`deploy/nginx.conf`), dan prosedur backup harian.

## Routes Publik

| Halaman | URL |
|----------|-----|
| Landing Page | `/` |
| Login | `/login` |
| Dokumentasi | `/docs` |
| Blog | `/blog` |
| Sitemap | `/sitemap.xml` |
| Tracking Servis | `/track/{token}` |
| Invoice Publik | `/invoice/{token}` |
| Approval Estimasi | `/approve/{token}` · `/reject/{token}` |

## Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Admin (super_admin) | admin@bengkel.test | password |
| Manager | manager@bengkel.test | password |
| Kasir | kasir@bengkel.test | password |
| Teknisi (mekanik) | teknisi@bengkel.test | password |
| Service Advisor | sa@bengkel.test | password |

## License

Proprietary. Hak cipta Bengkel Paten.
