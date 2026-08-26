# Deployment Guide — Bengkel Paten

## Server Requirements

- PHP 8.3+ (with extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`)
- MySQL 8.0+
- Node.js 20+ (for asset build)
- Nginx (recommended) or Apache
- Supervisor (for queue workers and scheduler)

## Step-by-Step Production Setup

### 1. Clone & Setup

```bash
cd /var/www/
git clone https://github.com/linducip2208/bengkel.git
cd bengkel

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 2. Environment Config

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dengan credentials production:

```env
APP_NAME="Bengkel Paten"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bengkel
DB_USERNAME=bengkel_user
DB_PASSWORD=strong-password-here

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Bengkel Paten"

# License v3 (Whitelabel.co.id)
LICENSE_PUBLIC_KEY_PATH=/var/www/bengkel/public/marketplace.public.pem
LICENSE_LOCK_PATH=/var/www/bengkel/storage/app/license.lock
LICENSE_API_URL=https://whitelabel.co.id/api/v1/license/verify
```

### 3. Database

```bash
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder
```

### 4. Storage & Permission

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Cache

```bash
php artisan optimize
```

### 6. Nginx Config

Copy `deploy/nginx.conf` ke `/etc/nginx/sites-available/bengkel`, lalu:

```bash
sudo ln -s /etc/nginx/sites-available/bengkel /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. Supervisor (Queue + Scheduler)

```bash
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/bengkel.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start bengkel-worker:*
```

### 8. SSL (Let's Encrypt)

```bash
sudo certbot --nginx -d domain-anda.com -d www.domain-anda.com
```

## Update Production

```bash
cd /var/www/bengkel
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force   # update role/permission (service_advisor dll)
php artisan optimize:clear
php artisan optimize
php artisan queue:restart
sudo supervisorctl restart bengkel-worker:*
```

### Migration terbaru (pastikan jalan semua)

Setelah update besar, jalankan `php artisan migrate:status` dan pastikan 0 pending. Migration baru meliputi:
- `companies` (multi-company) + `company_id` di branches
- `service_advisor_id`, `actual_cost` di services
- `started_at`/`finished_at` di service_technicians (time tracking)
- `part_reservations` (reservasi parts)
- `insurance_claims` (klaim asuransi)
- `fleet_contracts` + `fleet_contract_vehicles` (kontrak fleet)
- `technician_id` di bookings (appointment slot)
- `public_token` di invoices (shareable invoice link)
- `printers`, `invoice_schemes`, `bank_accounts`
- `purchase_requisitions`, `sell_returns`
- `product_variations`, `selling_price_groups`, `cash_denominations`
- `stock_adjustments`, `media_attachments`, `dashboard_configs`
- `sale_items` (penjualan sparepart), `tax_groups`, `purchase_orders`

### Catatan penting

- `SESSION_LIFETIME=1440` (24 jam) di `.env`
- `SESSION_DRIVER=database` untuk produksi (jangan `file`)
- Jangan jalankan `migrate:fresh` di produksi (hapus semua data)
- `db:seed` (DatabaseSeeder) = data demo. Produksi real cukup `PermissionSeeder` saja.

## Backup Database

Sudah ada scheduler daily backup di `routes/console.php` (jam 02:00 WIB). Output ke `/var/www/bengkel/storage/app/backups/`.

## Scheduler (15+ command otomatis)

Pastikan supervisor scheduler aktif (`php artisan schedule:work` atau cron `* * * * * php artisan schedule:run`):
- `backup:db` — daily 02:00
- `pos:close-stale-sessions` — hourly (auto-tutup sesi POS > 12 jam)
- `invoices:escalate-overdue` — hourly
- `services:escalate-overdue` — hourly (SLA)
- `notifications:process` — every 5 min
- `loyalty:birthday-vouchers` — daily 08:00
- `marketing:reactivation` — weekly Monday
- `reports:weekly-email` — weekly Monday 07:00
- `seo:indexnow` — daily 02:45

## Monitoring

- Cek queue worker: `sudo supervisorctl status bengkel-worker:*`
- Cek log: `tail -f storage/logs/laravel.log`
- Cek sitemap: `domain-anda.com/sitemap.xml`
- Cek robots: `domain-anda.com/robots.txt`
- Jalankan test: `php artisan test`
