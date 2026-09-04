# Bengkel Paten — Workshop ERP

**Bengkel Paten** is a multi-branch workshop ERP for vehicle service operations, customer management, estimates, approvals, work execution, quality control, invoicing, payments, inventory, accounting, and vehicle release.

**Bengkel Paten** adalah ERP bengkel multi-cabang untuk operasional servis kendaraan, pelanggan, estimasi, approval, pekerjaan teknisi, QC, invoice, pembayaran, inventory, accounting, dan pelepasan kendaraan.

**بنغل باتن** هو نظام ERP متكامل ومتعدد الفروع لإدارة ورش السيارات والعملاء والتقديرات والموافقات وتنفيذ الأعمال والفحص النهائي والفواتير والمدفوعات والمخزون والمحاسبة وتسليم المركبات.

## Canonical Workshop Flow / Alur Bengkel / سير عمل الورشة

```text
BOOKING / WALK-IN
  → CHECK-IN
  → CHECKLIST / INSPEKSI
  → TEMUAN / FINDING
  → WORK PACKAGE / RENCANA PEKERJAAN
  → ESTIMASI
  → APPROVAL CUSTOMER
  → PEKERJAAN / WORK TASK
  → QC
  → INVOICE
  → PAYMENT
  → GATE PASS / RELEASE
  → COMPLETED
```

### English

Every modern workshop service follows the persisted domain flow above. Findings and work packages are first-class stages; an estimate cannot silently skip them. Customer approval is per work-package group, and only approved work can create tasks. Invoice conversion uses approved commercial scope only.

### Bahasa Indonesia

Setiap Service modern mengikuti alur domain yang tersimpan di database. Temuan dan Rencana Pekerjaan adalah tahap wajib; Estimasi tidak melompati keduanya. Approval customer dilakukan per kelompok Work Package, dan hanya pekerjaan yang disetujui yang dapat dibuat menjadi Work Task. Invoice hanya berasal dari scope komersial yang disetujui.

### العربية

تتبع كل خدمة ورشة حديثة التسلسل المحفوظ في قاعدة البيانات. الملاحظات وحزم العمل مراحل أساسية ولا يمكن تجاوزها. تتم موافقة العميل لكل مجموعة من حزم العمل، ولا يمكن إنشاء مهام التنفيذ إلا للأعمال الموافق عليها. يتم إنشاء الفاتورة من النطاق التجاري الموافق عليه فقط.

## Features / Fitur / الميزات

### 1. Workshop operations / Operasional bengkel / عمليات الورشة

| English | Indonesia | العربية |
|---|---|---|
| Booking and walk-in intake | Booking dan penerimaan walk-in | الحجوزات واستقبال العملاء بدون حجز |
| Check-in and job cards | Check-in dan Job Card | تسجيل الدخول وبطاقات العمل |
| Inspection checklist with measurements and comments | Checklist inspeksi dengan pengukuran dan komentar | قائمة فحص مع القياسات والملاحظات |
| Automatic finding synchronization from actionable inspection results | Sinkronisasi otomatis temuan dari hasil inspeksi yang perlu tindakan | مزامنة الملاحظات تلقائياً من نتائج الفحص |
| Finding severity, source point, recommendation, status, resolve and defer | Severity, sumber titik checklist, rekomendasi, status, resolve dan defer | درجة الخطورة والمصدر والتوصية والحالة والتأجيل |
| Work Package / Rencana Pekerjaan with labor, parts, other cost and standard time | Rencana Pekerjaan dengan jasa, parts, biaya lain dan waktu standar | حزم العمل مع العمالة وقطع الغيار والتكاليف والوقت القياسي |
| Additional work through new finding → package → estimate revision → approval | Tambahan pekerjaan melalui finding → package → revisi estimasi → approval | الأعمال الإضافية عبر ملاحظة ثم حزمة عمل ثم مراجعة تقدير ثم موافقة |
| Technician work tasks with assignment and timers | Work Task teknisi dengan assignment dan timer | مهام الفنيين مع التعيين والمؤقت |
| Start, pause, resume and finish execution actions | Aksi mulai, jeda, lanjutkan dan selesai | بدء وإيقاف مؤقت واستئناف وإنهاء العمل |
| QC pass/fail, mandatory failure reason and rework | QC lulus/gagal, alasan wajib dan rework | نجاح/فشل الفحص النهائي وسبب الفشل وإعادة العمل |
| Service history and next-service reminders | Riwayat servis dan pengingat servis berikutnya | سجل الخدمة وتذكيرات الصيانة القادمة |

### 2. Estimates and customer approval / Estimasi dan approval / التقديرات وموافقة العميل

- Draft, sent, waiting approval, approved, partially approved, rejected, expired, superseded, and converted estimate statuses.
- Versioned estimate revisions with immutable historical versions.
- Work Package grouping, per-group customer decisions, approved/rejected/pending totals, approval evidence, public approval token, and WhatsApp sharing.
- Only approved groups proceed to execution and invoice conversion.
- Estimate is non-accounting: it creates no AR, journal, payment, or stock decrement.

**Indonesia:** Mendukung draft, terkirim, menunggu approval, disetujui penuh/sebagian, ditolak, kedaluwarsa, digantikan, revisi berversi, approval per group, link approval publik, dan pengiriman WhatsApp. Estimasi tidak membuat jurnal, piutang, pembayaran, atau pengurangan stok.

**العربية:** يدعم النظام المسودة والإرسال وانتظار الموافقة والموافقة الكاملة أو الجزئية والرفض والانتهاء والاستبدال والمراجعات المرقمة والموافقة لكل مجموعة ورابط الموافقة العام والمشاركة عبر واتساب. التقدير لا ينشئ قيداً محاسبياً أو ذمة مدينة أو دفعة أو خصماً من المخزون.

### 3. Invoice, payment and release / Invoice, pembayaran dan release / الفاتورة والدفع والتسليم

- Service invoices can be created from approved estimate scope after approved work is finished and QC has passed.
- Generic service-invoice bypasses are blocked for modern workshop services.
- POS, sales, sales-part, and explicit legacy service invoice flows remain supported.
- Approved-only invoice conversion excludes rejected, pending, and unapproved additional work.
- Invoice conversion is idempotent and protected against competing invoices.
- Partial payments, overpayment protection, idempotency keys, payment history, payment proof, and payment gateway links.
- Payment completion synchronizes the linked Service paid state.
- Gate Pass creation/update requires valid Service, QC passed, Ready-or-later status, branch consistency, and matching vehicle.
- Vehicle release requires QC passed, invoice existence, and full payment; repeated release is idempotent.
- Release synchronizes Service release/completion state and records ActivityLog events.

**Indonesia:** Invoice Service modern wajib melalui konversi Estimasi. Invoice tidak boleh memasukkan pekerjaan ditolak, pending, atau tambahan yang belum disetujui. Pembayaran mendukung cicilan, anti-overpayment, idempotency, bukti bayar, dan sinkronisasi status Service. Kendaraan hanya dapat keluar setelah QC lulus dan invoice lunas.

**العربية:** يجب إنشاء فاتورة الخدمة الحديثة من تحويل التقدير الموافق عليه. لا يمكن تضمين الأعمال المرفوضة أو المعلقة أو الإضافية غير الموافق عليها. يدعم الدفع الجزئي ومنع الدفع الزائد ومفتاح عدم التكرار وإثبات الدفع ومزامنة حالة الخدمة. لا يمكن تسليم المركبة قبل نجاح الفحص ودفع الفاتورة بالكامل.

### 4. Inventory and parts / Inventory dan parts / المخزون وقطع الغيار

- Products, product types, units, variations, selling-price groups, suppliers, supplier prices, warehouses, transfers, purchase orders, purchase requisitions, receiving, returns, stock adjustments, stock history, and reservations.
- Branch-aware stock records and immutable stock history.
- Approval creates reservation only; estimate draft/send never changes stock.
- Modern estimate invoice conversion is the single workshop consumption boundary; invoice reversal restores stock exactly once.
- POS and sales retain their existing stock consumption behavior.
- Negative stock is rejected by default through `STOCK_ALLOW_NEGATIVE=false`.

### 5. Accounting and finance / Accounting dan finance / المحاسبة والمالية

- Chart of accounts, journal entries and journal lines, income, expenses, petty cash, budgets, bank accounts, bank reconciliation, tax rates, tax groups, currencies, and payment methods.
- Invoice is the accounting boundary: AR/revenue and applicable COGS/inventory postings are created once.
- Payment posts cash/bank against AR once; retries do not duplicate journals.
- Invoice reversal restores stock and reverses accounting where allowed.
- Balanced journals are enforced by `AutoJournalService`.

### 6. Customer, vehicle and fleet / Customer, kendaraan dan fleet / العملاء والمركبات والأساطيل

- Customer master data, customer groups, customer login portal, vehicle brands/types, fuel types, cities, states, countries, colors, vehicle images, and service history.
- Fleet contracts, fleet contract vehicles, insurance claims, warranty claims, recalls, and supplier claims.
- Public service tracking, invoice links, surveys, reviews, loyalty transactions, vouchers, and campaigns.

### 7. POS and sales / POS dan penjualan / نقاط البيع والمبيعات

- POS sessions, cash denominations, held POS transactions, sales orders, sales parts, sale returns, invoice printing, payment methods, payment gateways, and payment links.
- Sales/POS flows are intentionally separate from the modern workshop invoice guard.

### 8. Reports and operations / Laporan dan operasional / التقارير والعمليات

- Sales, service, financial, stock, income, expense, technician commission, vehicle/service, and operational reporting.
- PDF and spreadsheet exports where supported.
- Dashboard service statistics, pending work, completed work, finance summaries, notifications, and branch-aware data.
- Workshop Service Detail displays authoritative current stage and next action from `WorkshopProgressService`.

### 9. Administration and security / Administrasi dan keamanan / الإدارة والأمان

- Multi-branch assignment and branch isolation for web and API data.
- Roles and permissions: `super_admin`, `admin`, `manager`, `kasir`, `mekanik`, `service_advisor`, and `inventory`.
- User, role, permission, activity log, notes, settings, invoice schemes, printers, custom fields, two-factor authentication, and license pairing.
- Private storage for payment proofs and attachments with authenticated download routes.
- Server-side authorization and business guards; UI visibility is not the only protection.

### 10. Marketing, documentation and SEO / Marketing, dokumentasi dan SEO / التسويق والتوثيق وتحسين البحث

- Public landing page, `/docs`, blog, RSS feed, sitemap, robots rules, public tracking, public estimate approval, and public invoice pages.
- Programmatic SEO pages for best services, alternatives, comparisons, categories, and blog content.
- JSON-LD, canonical/meta/OG support, dynamic sitemap generation, and IndexNow submission service/command.
- Blog categories, posts, publishing, SEO metadata, and seeded demo content.

## Main Routes / Route Utama / المسارات الرئيسية

| Area | Routes |
|---|---|
| Public | `/`, `/login`, `/docs`, `/blog`, `/blog/{slug}`, `/blog/feed.xml`, `/sitemap.xml`, `/robots.txt` |
| Workshop | `/bookings`, `/services`, `/services/{service}`, `/jobcards`, `/observations`, `/findings`, `/work-packages`, `/work-tasks`, `/qc` |
| Commercial | `/estimates`, `/invoices`, `/payments`, `/gate-passes` |
| Inventory | `/products`, `/purchases`, `/purchase-orders`, `/warehouses`, `/stock-adjustments`, `/stock-histories`, `/stock-transfers` |
| Finance | `/finance/coa`, `/finance/journal`, `/incomes`, `/expenses`, `/petty-cash`, `/budgets`, `/bank-accounts`, `/bank-reconciliations` |
| Customer | `/customers`, `/customer/dashboard`, `/customer/invoice/{id}`, `/track/{token}`, `/survey/{token}` |
| API | `/api/v1/bookings`, `/api/v1/services`, `/api/v1/invoices`, `/api/v1/invoices/{invoice}/payments`, `/api/v1/products`, `/api/v1/sales`, `/api/v1/reports/*` |

Run `php artisan route:list` for the complete route inventory.

## Technology / Teknologi / التقنية

- Laravel 12, PHP 8.3+
- MySQL 8 for production; SQLite supported for tests
- Blade, Bootstrap-based responsive custom UI, Vite
- Laravel Sanctum API authentication
- Spatie Laravel Permission
- DomPDF and spreadsheet export tooling
- Queue and scheduler support

## Requirements / Kebutuhan / المتطلبات

| Component | Version |
|---|---|
| PHP | ^8.3 with mbstring, PDO MySQL/SQLite, GD, ZIP |
| Composer | ^2 |
| Node.js / npm | Node.js ≥20 / npm ≥10 |
| Database | MySQL 8 production or SQLite testing |

## Installation / Instalasi / التثبيت

```bash
git clone https://github.com/linducip2208/bengkel.git
cd bengkel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed --force
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`. For production deployment, see [DEPLOYMENT.md](DEPLOYMENT.md).

**Indonesia:** Sesuaikan `DB_*`, `APP_URL`, queue, storage, mail, dan license di `.env`. `DatabaseSeeder` berisi data demo; untuk roles/permissions saja gunakan `PermissionSeeder`.

**العربية:** عدّل إعدادات قاعدة البيانات والرابط العام وقائمة الانتظار والتخزين والبريد والترخيص في ملف `.env`. يحتوي `DatabaseSeeder` على بيانات تجريبية، ويمكن استخدام `PermissionSeeder` للأدوار والصلاحيات فقط.

## Environment Variables / Variabel Lingkungan / متغيرات البيئة

| Variable | Meaning |
|---|---|
| `APP_DEBUG=false` | Keep disabled in production / wajib false di produksi / يجب تعطيله في الإنتاج |
| `DB_CONNECTION`, `DB_DATABASE` | Database connection |
| `QUEUE_CONNECTION=database` | Queue backend |
| `SESSION_DRIVER=database` | Session backend |
| `STOCK_ALLOW_NEGATIVE=false` | Reject negative stock by default |
| `LICENSE_DEV_BYPASS` | Development-only license pairing bypass |

## Demo Accounts / Akun Demo / الحسابات التجريبية

| Role / Peran / الدور | Email | Password |
|---|---|---|
| Admin / super_admin | `admin@bengkel.test` | `password` |
| Manager | `manager@bengkel.test` | `password` |
| Kasir / Cashier | `kasir@bengkel.test` | `password` |
| Teknisi / Mechanic | `teknisi@bengkel.test` | `password` |
| Service Advisor | `sa@bengkel.test` | `password` |

## Queue and Scheduler / Queue dan Scheduler / قائمة الانتظار والجدولة

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
php artisan schedule:run
```

Scheduled jobs include database backup, service reminders, overdue invoice escalation, notification delivery, loyalty reminders, and SEO/IndexNow tasks. See `routes/console.php` and `deploy/supervisor.conf`.

## Testing and Quality / Pengujian dan Quality Gate / الاختبارات والجودة

```bash
composer test
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan migrate:fresh --seed --env=testing --database=sqlite
php artisan route:list
php artisan config:cache
php artisan config:clear
npm run build
```

Critical coverage includes workshop canonical flow, invoice eligibility, approved-only invoice scope, payment idempotency, Gate Pass release, QC, additional work, branch isolation, accounting, inventory, and legacy compatibility.

## Branch Isolation / Isolasi Cabang / عزل الفروع

All modern workshop entities—Service, Estimate, Work Package, Work Task, Invoice, Payment, and Gate Pass—are branch-aware. Users with branch assignments can only select and view records in their active branch. API queries follow the authenticated branch context.

Semua entitas workshop modern mengikuti branch scope. Pengguna hanya dapat memilih dan melihat data pada cabang aktifnya. API juga mengikuti konteks cabang pengguna.

جميع كيانات الورشة الحديثة مرتبطة بنطاق الفرع. لا يمكن للمستخدم اختيار أو عرض بيانات فرع آخر، كما تتبع طلبات API سياق فرع المستخدم المصادق عليه.

## Accounting and Stock Boundary / Batas Accounting dan Stok / حدود المحاسبة والمخزون

Estimate and approval are commercial/operational only. Approval reserves parts but does not decrement stock. Invoice is the accounting boundary and the single modern workshop stock-consumption boundary. Payment posts collection against receivables. Gate Pass/release is operational and does not create a second invoice or inventory flow.

## Documentation / Dokumentasi / التوثيق

- [Deployment guide](DEPLOYMENT.md)
- [Architecture decisions](docs/ARCHITECTURE_DECISIONS.md)
- [Public documentation page](/docs)
- `routes/web.php` for complete route definitions
- `app/Services/WorkshopProgressService.php` for authoritative workshop-stage projection
- `app/Services/WorkshopInvoiceGuard.php` for modern service-invoice eligibility
- `app/Services/GatePassEligibilityService.php` for Gate Pass and release eligibility

## License / Lisensi / الترخيص

Proprietary software. Copyright © Bengkel Paten.
