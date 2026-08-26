# Analisis Aplikasi Garage Master — Blueprint Bengkel Baru

> Dianalisis: 2026-04-30  
> Source: codecanyon-22652605 (Garage Master v4.6.5)  
> Stack: Laravel 12, PHP 8.2, MySQL, Bootstrap

---

## 1. Ringkasan Aplikasi

Garage Master adalah sistem manajemen bengkel multi-cabang yang mencakup seluruh alur operasional: dari registrasi customer dan kendaraan, service/jobcard, quotation, invoice, inventory parts, hingga laporan keuangan. Cocok sebagai referensi arsitektur untuk membangun aplikasi bengkel baru.

---

## 2. Stack Teknologi

| Komponen | Versi |
|---|---|
| Laravel | ^12.0 |
| PHP | ^8.2 |
| UI Framework | Bootstrap (Laravel UI 4.0) |
| PDF | carlos-meneses/laravel-mpdf ^2.1 |
| Payment | stripe/stripe-php ^15.8 |
| HTTP Client | guzzlehttp/guzzle ^7.9 |
| JS Validation | proengsoft/laravel-jsvalidation ^4.9 |
| Database | MySQL (MyISAM engine) |

---

## 3. Modul & Fitur yang Ada

### 3.1 Customer Management
- CRUD customer (nama, email, telepon, alamat, company, tax ID, NPWP, bank)
- View semua kendaraan, jobcard, quotation, invoice, pembayaran per customer
- MOT tracking per customer
- Notes & attachments polymorphic
- CSV import customer
- Kanban board view
- Custom fields

### 3.2 Vehicle Management
- Registrasi kendaraan lengkap: plat, rangka, mesin, tahun, warna, BBM, odometer
- Master data: vehicle types, brands, models, fuel types, colors
- Foto kendaraan multiple
- Deskripsi & maintenance history
- MOT inspection record
- Validasi nomor plat unik
- CSV import kendaraan

### 3.3 Service / Jobcard
- Buat service request dari customer/kendaraan
- Kategori: Breakdown, Booked, Repeat Job, Customer Waiting
- Assign ke teknisi/employee
- Free service vs paid service
- Observation points & checkpoint inspection
- Foto before/after service
- Tax per service
- Dukungan kupon/voucher
- Status: Open → In Process → Done
- MOT status flag
- Quotation-linked service

### 3.4 Job Card System
- Nomor jobcard unik (job_no)
- Tanggal masuk/keluar kendaraan
- Odometer masuk & rekomendasi service berikutnya
- Checkpoint & observation system (bisa custom)
- Gate pass generation
- Tambah produk/parts ke jobcard
- Comment per checkpoint point
- Complete process tracking
- Reminder flag (reminder_sent)

### 3.5 Quotation
- Buat quotation dari service
- Edit & modifikasi quotation
- Finalisasi quotation → convert ke jobcard
- Status tracking quotation
- Kirim via email
- Generate PDF
- Observasi points di quotation

### 3.6 Invoice
- 3 tipe invoice: Service, Sales, Sales Part
- Buat dari jobcard / service / sales
- Payment tracking (Unpaid / Half Paid / Full Paid)
- Multiple payment methods
- Stripe payment integration
- Kirim invoice via email
- Generate PDF
- Custom fields

### 3.7 Inventory / Parts
- CRUD produk dengan kode, kategori, unit, harga, supplier
- Tipe produk & unit master
- Foto produk
- Stock tracking per branch
- Manual stock adjustment
- Stock report (print & PDF)
- CSV import produk

### 3.8 Purchase (Pembelian dari Supplier)
- Purchase order ke supplier
- Multi-produk per PO
- History pembelian
- Stock otomatis bertambah setelah purchase
- Notes per PO

### 3.9 Sales (Penjualan Kendaraan)
- Penjualan kendaraan ke customer
- Linked ke vehicle record
- Taxes per sales
- Salesperson tracking
- Auto-schedule service setelah penjualan (interval + jumlah service)
- Invoice penjualan

### 3.10 Sales Parts
- Penjualan parts langsung (tanpa service)
- Invoice terpisah
- Stock otomatis berkurang

### 3.11 Supplier Management
- CRUD supplier
- Contact info
- Produk per supplier
- Notes & attachments
- CSV import

### 3.12 Employee Management
- Data karyawan lengkap
- Service assignments
- Free / Paid / Repeat service tracking
- Access rights per employee

### 3.13 Support Staff & Accountant
- Role terpisah dengan akses berbeda
- CRUD lengkap

### 3.14 Gate Pass
- Generate gate pass kendaraan
- Tracking keluar/masuk
- Print & PDF

### 3.15 Observation & Checkpoint
- Library observation types & points
- Custom inspection checklist
- Link ke service/quotation

### 3.16 Income & Expense
- Manual input income & expense
- Monthly reports
- History changes

### 3.17 Reports
- Sales report
- Service report
- Product usage report
- Stock report
- Employee service report
- Upcoming service report
- Email log report
- PDF generation semua report

### 3.18 Branch Management (Multi-Cabang)
- Buat cabang baru
- Branch-specific settings
- Branch admin role
- Semua data isolated per branch

### 3.19 Settings
- General settings (nama sistem, logo, alamat)
- Email config (SMTP)
- Payment gateway (Stripe)
- SMS settings (framework tersedia, implementasi minimal)
- Multi-language (LTR/RTL)
- Timezone & currency
- Custom fields dinamis per modul
- Business hours & holidays
- Email templates (10+ template)
- Quotation settings
- License management

### 3.20 Access Rights / RBAC
- 6 role: Super Admin, Customer, Employee, Support Staff, Accountant, Branch Admin
- Permission per menu per role
- Custom access control

### 3.21 Fitur Tambahan
- Frontend booking (public page untuk customer)
- Dashboard dengan metrics open/close/upcoming services
- Sample data generator
- Kanban board workflow
- CSV import/export
- Notes system (polymorphic, bisa di semua entitas)
- MOT inspection (British MOT test compliance)
- RTO tax management
- OTP login
- Add-on marketplace page
- How-to videos page

---

## 4. Database Schema — 73 Tabel

### 4.1 Tabel Utama

| Tabel | Keterangan |
|---|---|
| `users` | Semua user: customer, employee, admin, supplier, staff |
| `roles` | Role definitions |
| `role_users` | Junction table user-role (many-to-many) |
| `tbl_vehicles` | Data kendaraan |
| `tbl_services` | Service request / jobcard header |
| `tbl_jobcard_details` | Detail jobcard (tanggal, odometer, status) |
| `tbl_invoices` | Invoice (service, sales, parts) |
| `tbl_products` | Master produk/parts |
| `tbl_stock_records` | Stok per produk per cabang |
| `tbl_purchases` | Purchase order ke supplier |
| `tbl_sales` | Penjualan kendaraan |
| `tbl_sale_parts` | Penjualan parts langsung |
| `branches` | Cabang |
| `tbl_settings` | Pengaturan sistem |

### 4.2 Tabel Master Data

| Tabel | Keterangan |
|---|---|
| `tbl_vehicle_types` | Jenis kendaraan |
| `tbl_vehicle_brands` | Brand kendaraan → FK ke vehicle_types |
| `tbl_model_names` | Model kendaraan |
| `tbl_fuel_types` | Jenis BBM |
| `tbl_colors` | Master warna |
| `tbl_product_types` | Kategori produk |
| `tbl_product_units` | Satuan produk (pcs, liter, dll) |
| `tbl_payments` | Metode pembayaran |
| `tbl_account_tax_rates` | Tarif pajak (VAT, GST, dll) |
| `tbl_rto_taxes` | Pajak RTO |
| `tbl_countries` | Negara |
| `tbl_states` | Provinsi |
| `tbl_cities` | Kota |
| `currencies` | 138+ mata uang |

### 4.3 Tabel Operasional

| Tabel | Keterangan |
|---|---|
| `tbl_service_images` | Foto per service |
| `tbl_service_taxes` | Tax per service |
| `tbl_service_pros` | Teknisi per service |
| `tbl_service_observation_points` | Mapping service → observation |
| `tbl_vehicle_images` | Foto kendaraan |
| `tbl_vehicle_colors` | Warna per kendaraan |
| `tbl_vehicle_discription_records` | Deskripsi kendaraan |
| `tbl_observation_types` | Tipe observasi |
| `tbl_observation_points` | Point observasi |
| `inspection_points_library` | Library standar inspeksi |
| `tbl_checkout_categories` | Kategori checkout kendaraan |
| `tbl_checkout_results` | Hasil checkout |
| `tbl_gatepasses` | Gate pass kendaraan |
| `washbays` | Area cuci kendaraan |
| `mot_vehicle_inspection` | MOT inspection record |
| `vehicle_mot_test_reports` | MOT test report |
| `table_repair_category` | Kategori repair |

### 4.4 Tabel Finansial

| Tabel | Keterangan |
|---|---|
| `tbl_incomes` | Pemasukan |
| `tbl_income_history_records` | History perubahan income |
| `tbl_expenses` | Pengeluaran |
| `tbl_expenses_history_records` | History perubahan expense |
| `tbl_payment_records` | Record pembayaran invoice |
| `tbl_add_payments` | Pembayaran tambahan |
| `tbl_purchase_history_records` | History pembelian |
| `tbl_sales_taxes` | Tax per sales |

### 4.5 Tabel System

| Tabel | Keterangan |
|---|---|
| `tbl_custom_fields` | Definisi custom fields |
| `tbl_business_hours` | Jam kerja |
| `tbl_holidays` | Hari libur |
| `tbl_mail_notifications` | Template notifikasi email |
| `email_logs` | Log email terkirim |
| `tbl_accessrights` | Hak akses per menu |
| `notes` | Catatan polymorphic |
| `note_attachments` | Lampiran per catatan |
| `tbl_language_directions` | Arah bahasa (LTR/RTL) |
| `updatekey` | License key tracking |
| `migrations` | Laravel migrations |
| `password_resets` | Reset password tokens |

---

## 5. ERD — Relasi Antar Tabel

```
users (1) ──────────────────────── (*) role_users (*) ──── (1) roles
  │
  ├─(1)──(*) tbl_vehicles
  │             │
  │             ├─(*) tbl_vehicle_types
  │             ├─(*) tbl_vehicle_brands
  │             ├─(*) tbl_fuel_types
  │             ├─(1)──(*) tbl_vehicle_images
  │             ├─(1)──(*) tbl_vehicle_colors
  │             ├─(1)──(*) tbl_vehicle_discription_records
  │             └─(1)──(*) tbl_services ─────────────────────────────┐
  │                           │                                       │
  ├─(1)──(*) tbl_services     ├─(1)──(*) tbl_jobcard_details         │
  │                           ├─(1)──(*) tbl_service_images          │
  ├─(1)──(*) tbl_sales        ├─(1)──(*) tbl_service_taxes           │
  │             │             ├─(1)──(*) tbl_service_observation_pts  │
  │             ├─(1)──(*) tbl_services                              │
  │             ├─(1)──(*) tbl_invoices                              │
  │             └─(1)──(*) tbl_sale_parts                            │
  │                                                                   │
  ├─(1)──(*) tbl_invoices ◄───────────────────────────────────────────┘
  │
  ├─(1)──(*) tbl_products (as supplier)
  │             │
  │             └─(1)──(*) tbl_stock_records
  │
  └─(*) branches (1) ──────────────────────────
                         ├─(*) tbl_vehicles
                         ├─(*) tbl_services
                         ├─(*) tbl_invoices
                         ├─(*) tbl_products
                         ├─(*) tbl_stock_records
                         ├─(*) tbl_purchases
                         ├─(*) tbl_sales
                         ├─(*) tbl_incomes
                         ├─(*) tbl_expenses
                         └─(*) tbl_gatepasses

notes (morphable ke: users, vehicles, services, invoices, products, purchases, sales)
  └─(1)──(*) note_attachments
```

### Alur Data Utama

```
ALUR SERVICE:
Customer → Kendaraan → Service Request → Jobcard → Inspeksi → Selesai → Invoice → Bayar → Income

ALUR PENJUALAN KENDARAAN:
Customer → Sales → Invoice → (Auto-create schedule service berikutnya)

ALUR INVENTORY:
Supplier → Purchase Order → Stock bertambah → Dipakai di Service/Sales → Stock berkurang

ALUR FINANSIAL:
Invoice (Paid) → Income Record
Manual Entry → Expense Record
Monthly: Income - Expense = Profit
```

---

## 6. Fitur yang Kurang / Perlu Ditambah di Aplikasi Baru

### PRIORITAS TINGGI (Wajib ada di bengkel Indonesia)

#### 6.1 WhatsApp Notification (Pengganti/Tambahan Email)
- **Problem:** Garage Master hanya punya email notification. Di Indonesia, WhatsApp jauh lebih efektif.
- **Kebutuhan:**
  - Kirim konfirmasi booking via WA
  - Notifikasi service selesai via WA
  - Kirim invoice/kwitansi via WA (link atau PDF)
  - Reminder service berikutnya via WA
  - Template WA yang bisa dikustomisasi admin
- **Implementasi:** Gunakan format-based adapter (WA Business API, Fonnte, Wablas, dll — user pilih sendiri di admin)

#### 6.2 Reminder Otomatis (Scheduled)
- **Problem:** Ada field `reminder_sent` di jobcard tapi tidak ada scheduler. Harus manual.
- **Kebutuhan:**
  - Auto-reminder H-7, H-3, H-1 sebelum jadwal service
  - Reminder berdasarkan odometer (misal: "saatnya ganti oli di 5.000 km lagi")
  - Kirim via email dan/atau WA
  - Admin bisa atur template & timing reminder
- **Implementasi:** Laravel Scheduler + Queue

#### 6.3 Antrian / Queue Management
- **Problem:** Tidak ada sistem antrian. Walk-in customer tidak bisa tahu posisi antrian.
- **Kebutuhan:**
  - Nomor antrian per hari
  - Display board antrian (bisa tampil di TV/monitor bengkel)
  - Estimasi waktu tunggu
  - Notifikasi WA saat giliran hampir tiba

#### 6.4 Estimasi Waktu Selesai (ETA)
- **Problem:** Customer tidak tahu kapan kendaraan selesai.
- **Kebutuhan:**
  - Teknisi input estimasi durasi per pekerjaan
  - Customer bisa cek status real-time via link/portal
  - Auto-update ETA saat ada penambahan pekerjaan

#### 6.5 Customer Self-Service Portal
- **Problem:** Frontend booking ada, tapi customer tidak bisa tracking status service sendiri.
- **Kebutuhan:**
  - Login customer
  - Lihat status service real-time
  - Lihat invoice & riwayat service
  - Approve/reject additional work (digital approval)
  - Download invoice

#### 6.6 Digital Signature & Approval
- **Problem:** Tidak ada persetujuan digital dari customer.
- **Kebutuhan:**
  - Customer sign inspection form secara digital (touchscreen/link)
  - Approve tambahan pekerjaan via WA/portal
  - Tandatangan di gate pass keluar

#### 6.7 Foto Sistematis Before/After
- **Problem:** Ada tbl_service_images tapi tidak ada flow wajib foto before/after.
- **Kebutuhan:**
  - Wajib foto kendaraan saat masuk (4 sisi)
  - Foto progres pekerjaan
  - Foto kendaraan selesai
  - Customer bisa lihat di portal
  - Watermark otomatis (tanggal, jam, nomor jobcard)

---

### PRIORITAS MENENGAH

#### 6.8 Loyalty Program / Poin Member
- **Problem:** Tidak ada reward customer untuk mendorong repeat order.
- **Kebutuhan:**
  - Poin per transaksi
  - Redeem poin sebagai diskon
  - Level member (Silver, Gold, Platinum)
  - Histori poin

#### 6.9 Manajemen Garansi Parts
- **Problem:** Ada field `warranty` di produk tapi tidak ada tracking garansi yang dipakai di service.
- **Kebutuhan:**
  - Catat garansi per part yang dipasang di kendaraan
  - Alert saat garansi hampir habis
  - Klaim garansi ke supplier

#### 6.10 Supplier Price List & Perbandingan Harga
- **Problem:** Tidak ada fitur bandingkan harga antar supplier.
- **Kebutuhan:**
  - Input harga dari multiple supplier per produk
  - Saran supplier termurah saat buat PO
  - History pergerakan harga beli

#### 6.11 Barcode / QR Code
- **Problem:** Tidak ada sistem barcode.
- **Kebutuhan:**
  - QR code per kendaraan (print di dashboard kendaraan)
  - Barcode per produk/parts (untuk scan saat ambil dari gudang)
  - QR di invoice untuk verifikasi customer

#### 6.12 Labor Cost Tracking
- **Problem:** Biaya jasa tidak dipisah dari biaya parts.
- **Kebutuhan:**
  - Input jam kerja per teknisi per jobcard
  - Rate upah per jam per teknisi
  - Otomatis hitung biaya jasa
  - Profitabilitas per jobcard: revenue - parts cost - labor cost

#### 6.13 Fleet / Korporat Management
- **Problem:** Tidak ada fitur khusus untuk customer korporat dengan banyak kendaraan.
- **Kebutuhan:**
  - Grup kendaraan per perusahaan
  - Invoice korporat (satu invoice untuk multiple kendaraan)
  - Batas kredit per korporat
  - Monthly billing

#### 6.14 Insurance Claim Management
- **Problem:** Kendaraan dari asuransi tidak punya flow tersendiri.
- **Kebutuhan:**
  - Flag "insurance job"
  - Input data asuransi (nama perusahaan, nomor polis, surveyor)
  - Approval dari surveyor
  - Invoice ke asuransi (berbeda dengan invoice ke customer)

#### 6.15 Kredit / Cicilan Customer
- **Problem:** Pembayaran hanya cash/transfer. Tidak ada kredit.
- **Kebutuhan:**
  - Partial payment dengan jadwal cicilan
  - Reminder jatuh tempo cicilan
  - Penalti keterlambatan

---

### PRIORITAS RENDAH (Nice to Have)

#### 6.16 Analitik Dashboard yang Lebih Kaya
- **Problem:** Dashboard ada tapi metrics terbatas.
- **Tambahkan:**
  - Revenue per teknisi
  - Parts usage trend
  - Customer retention rate
  - Average service value
  - Peak hours analysis
  - Grafik interaktif (Chart.js/ApexCharts)

#### 6.17 Purchase Order Approval Workflow
- **Problem:** PO langsung dibuat tanpa approval.
- **Kebutuhan:**
  - Request → Approval Manager → Approved → PO ke supplier
  - Batas nilai PO yang butuh approval

#### 6.18 Manajemen Gudang (Multi-lokasi)
- **Problem:** Stok hanya per cabang, tidak per lokasi/rak.
- **Kebutuhan:**
  - Rak/lokasi penyimpanan per produk
  - Minimum stock alert
  - Auto reorder point
  - Transfer stok antar cabang

#### 6.19 Customer Satisfaction Survey
- **Kebutuhan:**
  - Kirim survey otomatis setelah service selesai (WA/email)
  - Rating 1-5 bintang
  - Komentar bebas
  - Dashboard rating per teknisi

#### 6.20 Two-Factor Authentication (2FA)
- **Kebutuhan:**
  - 2FA via OTP WA/email untuk admin/owner
  - Login history

#### 6.21 Backup & Restore Database
- **Kebutuhan:**
  - Scheduled backup otomatis
  - Download backup manual
  - Restore dari file backup

#### 6.22 Progressive Web App (PWA)
- **Kebutuhan:**
  - Akses offline untuk cek jobcard
  - Push notification di browser
  - Add to homescreen

#### 6.23 API REST Lengkap untuk Integrasi
- **Problem:** Ada API endpoint tapi sangat minimal dan tidak terdokumentasi.
- **Kebutuhan:**
  - REST API dengan autentikasi token
  - Swagger/OpenAPI documentation
  - Endpoint untuk semua modul utama
  - Webhook untuk event (service selesai, invoice dibuat, dll)

#### 6.24 Dark Mode
- **Problem:** Tidak ada dark mode.

#### 6.25 Laporan Pajak
- **Kebutuhan:**
  - Rekap PPN per periode
  - Export format Excel untuk laporan pajak
  - Faktur pajak digital

---

## 7. Masalah Teknis yang Perlu Diperbaiki di Aplikasi Baru

### 7.1 Database Engine
- **Problem:** Semua tabel menggunakan `MyISAM` — tidak support foreign key constraint dan tidak ACID compliant.
- **Solusi:** Gunakan `InnoDB` dengan proper foreign keys.

### 7.2 Tidak Ada Proper Foreign Key di Database
- **Problem:** Relasi hanya di level Eloquent, tidak di database. Data orphan bisa terjadi.
- **Solusi:** Definisikan FK di migration dengan `onDelete('cascade')` atau `onDelete('restrict')`.

### 7.3 File PHP Monolitik
- **Problem:** `instaltionController.php` = 1.8MB, 55.000+ baris. `HomeController.php` = 96KB. `InvoiceController.php` = 127KB.
- **Solusi:** Pisah ke Service classes, Repository pattern, Action classes.

### 7.4 Password Hashing di Installer
- **Problem:** Installer pakai `password_hash()` bukan `bcrypt()`/`Hash::make()` Laravel.
- **Solusi:** Konsisten pakai `Hash::make()`.

### 7.5 Soft Delete Custom
- **Problem:** Pakai kolom `soft_delete` sendiri (0/1), bukan Laravel `SoftDeletes` trait.
- **Solusi:** Pakai `SoftDeletes` trait standar Laravel supaya bisa pakai `withTrashed()`, `onlyTrashed()`, dll.

### 7.6 Nama Controller & File Tidak Konsisten
- **Problem:** `VehicalControler.php` (salah eja), `employeecontroller.php` (lowercase), `Customercontroller.php`, `instaltionController.php`.
- **Solusi:** PascalCase konsisten untuk semua controller.

### 7.7 Tidak Ada API Rate Limiting
- **Problem:** API endpoint tidak ada rate limiting.
- **Solusi:** Tambahkan throttle middleware.

### 7.8 SMS Settings Ada Tapi Tidak Berfungsi
- **Problem:** Ada halaman SMS settings tapi tidak ada implementasi pengiriman SMS.
- **Solusi:** Implementasikan SMS gateway menggunakan format-based adapter.

---

## 8. Rekomendasi Arsitektur untuk Aplikasi Bengkel Baru

### 8.1 Layer Architecture
```
routes/
  web.php          (HTML routes)
  api.php          (REST API routes)
  
app/
  Http/
    Controllers/   (Thin controllers — hanya handle request/response)
    Requests/      (Form validation)
    Resources/     (API transformers)
  
  Services/        (Business logic)
    ServiceJobService.php
    InvoiceService.php
    NotificationService.php
    InventoryService.php
    
  Repositories/    (Database queries)
    ServiceRepository.php
    InvoiceRepository.php
    
  Actions/         (Single-purpose operations)
    CreateJobCard.php
    CompleteService.php
    GenerateInvoice.php
    SendServiceNotification.php
    
  Events/          (Domain events)
    ServiceCompleted.php
    InvoiceCreated.php
    
  Listeners/       (React to events)
    SendCompletionWhatsApp.php
    UpdateStockOnPurchase.php
```

### 8.2 Modul Prioritas Build (urutan)
1. Auth + RBAC
2. Customer + Kendaraan
3. Service / Jobcard
4. Invoice + Pembayaran
5. Inventory + Purchase
6. Notifikasi (WA + Email)
7. Reports
8. Customer Portal
9. Branch management
10. Advanced features (loyalty, fleet, insurance)

### 8.3 Database Best Practices
- Semua tabel: engine `InnoDB`
- Foreign keys dengan constraint di migration
- Index pada kolom yang sering di-query (customer_id, vehicle_id, service_date, status)
- Proper `SoftDeletes` trait
- `timestamps()` di semua tabel
- UUID sebagai primary key (optional, untuk API-friendly)

### 8.4 Notification Architecture
```
NotificationChannel (abstrak)
  ├── EmailChannel       (via SMTP — sudah ada)
  ├── WhatsAppChannel    (via WA Business API adapter)
  ├── SmsChannel         (via SMS gateway adapter)
  └── PushChannel        (via FCM/APNS)

NotificationTemplate
  ├── service_created
  ├── service_completed  
  ├── invoice_created
  ├── payment_received
  ├── service_reminder
  └── welcome_customer
```

---

## 9. Data Test yang Perlu Diisi

Untuk belajar semua menu, isi data berikut secara berurutan:

1. **Settings** → General: isi nama bengkel, logo, alamat, kontak
2. **Vehicle Types** → Tambah: Mobil, Motor, Truk
3. **Vehicle Brands** → Tambah: Toyota, Honda, Yamaha, Suzuki
4. **Payment Methods** → Tambah: Cash, Transfer Bank, Kartu Kredit
5. **Tax Rates** → Tambah: PPN 11%
6. **Products** → Tambah 5-10 produk (Oli, Filter Oli, Busi, Kampas Rem, Aki)
7. **Supplier** → Tambah 2-3 supplier
8. **Purchase** → Buat PO dari supplier, isi produk → stok bertambah
9. **Employee** → Tambah 2-3 teknisi
10. **Customer** → Tambah 3-5 customer
11. **Vehicle** → Tambah 1-2 kendaraan per customer
12. **Service** → Buat service request → buat jobcard → isi checkpoint → selesaikan
13. **Invoice** → Generate dari service → tambah payment
14. **Quotation** → Buat quotation → send ke customer → finalize → convert ke jobcard
15. **Sales** → Buat penjualan kendaraan → generate invoice
16. **Reports** → Cek semua laporan dengan data yang sudah ada
17. **Branch** → Buat cabang kedua → cek isolasi data

---

## 10. Checklist Menu yang Perlu Diverifikasi Fungsinya

### Modul Wajib Test
- [ ] Login / Logout / Reset Password / OTP Login
- [ ] Dashboard metrics (open/close/upcoming)
- [ ] Tambah customer → edit → hapus
- [ ] Tambah kendaraan → link ke customer
- [ ] Buat service → assign teknisi → process → complete
- [ ] Generate jobcard → add checkpoint → gate pass
- [ ] Buat quotation → PDF → email → convert ke jobcard
- [ ] Generate invoice service → bayar → cek status Paid
- [ ] Buat purchase → cek stok bertambah
- [ ] Buat sales kendaraan → generate invoice
- [ ] Laporan sales & service dengan filter tanggal
- [ ] CSV import customer/kendaraan/produk
- [ ] Ganti bahasa (jika multi-language aktif)
- [ ] Buat cabang → login sebagai branch admin → cek akses terbatas

### Fitur yang Mungkin Bermasalah (Perlu Extra Test)
- [ ] Email sending (butuh SMTP config valid)
- [ ] SMS notifications (kemungkinan belum berfungsi)
- [ ] Stripe payment (butuh Stripe keys)
- [ ] MOT inspection (fitur British, mungkin tidak relevan)
- [ ] License OTP verification (butuh server license online)
- [ ] Addon marketplace (mungkin butuh koneksi server vendor)

---

---

## 11. Fitur Tambahan yang Bisa Dibangun (Extended Feature List)

> Semua fitur di bawah ini **tidak ada** di Garage Master dan bisa menjadi differentiator kuat untuk aplikasi bengkel baru.

---

### 11.A OPERASIONAL BENGKEL

#### Manajemen Loaner Car / Mobil Pinjaman
- Customer titip kendaraan → bengkel pinjamkan kendaraan pengganti
- Tracking kendaraan loaner: sedang dipinjam siapa, kapan kembali
- Deposit management
- Kondisi kendaraan saat keluar/kembali (foto + checklist)

#### Subkontraktor / Pekerjaan Keluar
- Beberapa pekerjaan dikirim ke bengkel spesialis (misal: balancing, spooring, AC)
- Tracking biaya subkon per jobcard
- Estimasi selesai dari subkon
- Margin tracking: harga ke customer vs biaya subkon

#### Servis Panggilan / On-Site Service
- Customer minta teknisi datang ke lokasi
- Tracking lokasi (GPS koordinat)
- Biaya perjalanan otomatis dihitung
- Status teknisi: di jalan, sampai, selesai

#### Towing & Antar Jemput Kendaraan
- Request towing dari customer
- Assign pengemudi
- Biaya towing per km otomatis
- Tracking status: dijemput, di perjalanan, sudah tiba
- Integrasi notifikasi WA real-time

#### Paket Servis (Service Package)
- Bundling beberapa service: misal "Paket Tune-Up Lengkap" = ganti oli + filter + busi + cek rem
- Harga paket lebih murah dari satuan (harga bundel)
- Customer bisa beli paket di muka, dipakai bertahap
- Tracking sisa paket per customer

#### Pre-booking Parts / Reservasi Stok
- Saat buat quotation/jobcard, langsung reserve stok parts
- Stok tidak bisa dijual ke orang lain sampai jobcard selesai/dibatalkan
- Alert jika stok tidak cukup untuk semua reservasi

#### Wash Bay Management (Sudah ada tabel `washbays` tapi belum diimplementasi)
- Jadwal antrian wash bay
- Status bay: kosong, terpakai, dalam antrian
- Tracking waktu cuci per kendaraan
- Billing cuci terpisah atau bundle dengan service

---

### 11.B KEUANGAN & PEMBAYARAN

#### POS (Point of Sale) untuk Counter Sales
- Mode kasir untuk penjualan parts walk-in
- Layar kasir + receipt thermal printer
- Barcode scan produk
- Multiple cashier support
- Shift kasir (buka/tutup kasir dengan laporan)
- Cash drawer integration

#### Petty Cash Management
- Kas kecil harian bengkel
- Input pengeluaran kecil (beli kopi, fotokopi, dll)
- Rekonsiliasi harian
- Laporan pengeluaran kas kecil

#### Payroll Teknisi
- Gaji pokok + komisi per jobcard
- Komisi berdasarkan: jumlah jobcard, revenue yang dihasilkan, atau penilaian
- Bonus target bulanan
- Slip gaji digital (kirim via WA)
- Potongan: absensi, keterlambatan, kerusakan

#### Kredit Customer & Manajemen Piutang
- Batas kredit per customer
- Invoice belum dibayar masuk daftar piutang
- Aging piutang (0-30 hari, 30-60 hari, 60-90 hari, >90 hari)
- Reminder otomatis tagihan jatuh tempo
- Blacklist customer yang sering telat bayar

#### Deposit / Down Payment
- Customer bayar DP sebelum service dikerjakan
- Tracking sisa pembayaran
- Refund DP jika pekerjaan dibatalkan

#### Profit Per Jobcard
- Hitung profit real per jobcard: revenue - harga pokok parts - biaya labor
- Margin alert: notifikasi jika margin di bawah threshold
- Ranking jobcard paling profitable

#### QRIS / Pembayaran Digital Indonesia
- Integrasi QRIS (format-based adapter untuk berbagai bank/e-wallet)
- GoPay, OVO, Dana, ShopeePay, BRIVA, Mandiri VA, dll
- Auto-confirm pembayaran via webhook
- Rekap transaksi digital per hari

#### Multi-Mata Uang untuk Bengkel Import Parts
- Beli parts dalam USD/SGD dari supplier luar
- Auto-konversi ke IDR berdasarkan kurs hari itu
- Tracking keuntungan/kerugian kurs

---

### 11.C INVENTORY LANJUTAN

#### Auto Reorder Point
- Tentukan stok minimum per produk
- Sistem otomatis buat draft PO ke supplier jika stok di bawah minimum
- Notifikasi ke admin/purchasing

#### Parts Compatibility Database
- Setiap parts dikaitkan dengan: merek kendaraan + model + tahun + cc
- Saat teknisi tambah parts ke jobcard, otomatis filter hanya parts yang cocok untuk kendaraan tersebut
- Rekomendasi parts alternatif (OEM vs aftermarket)

#### Serial Number / Batch Tracking
- Parts mahal (aki, ban, kaca) dicatat serial numbernya
- Tracking mana serial number dipasang di kendaraan mana
- Penting untuk klaim garansi

#### Stock Opname Digital
- Mode stock opname: hitung fisik semua stok
- Input dari tablet/HP per rak
- Laporan selisih: sistem vs fisik
- Adjustment otomatis setelah approval

#### Konsinyasi Supplier
- Supplier titip barang di bengkel, bayar kalau sudah terjual
- Tracking barang konsinyasi terpisah dari stok beli
- Rekonsiliasi bulanan dengan supplier konsinyasi

#### Expired Parts Alert
- Input tanggal kadaluarsa untuk parts yang punya expiry (oli, cairan rem, dll)
- Alert H-30 sebelum expired
- Laporan parts hampir/sudah expired

#### Dead Stock Report
- Identifikasi produk yang tidak terjual >90 hari
- Rekomendasi: diskon, return ke supplier, atau hapus dari katalog

---

### 11.D PELANGGAN & CRM

#### STNK / Pajak Kendaraan Reminder (Indonesia-Specific)
- Input tanggal jatuh tempo STNK setiap kendaraan
- Reminder otomatis H-30, H-14, H-7 via WA
- Reminder juga untuk perpanjangan BPKB
- Customer sangat appreciate fitur ini — loyalty builder kuat

#### KIR Reminder (Kendaraan Niaga)
- Tracking jadwal uji KIR untuk angkutan/truk/bus
- Reminder H-30 sebelum jatuh tempo
- Dokumen KIR bisa di-upload ke sistem

#### Asuransi Kendaraan Reminder
- Input tanggal berakhir asuransi
- Reminder perpanjangan + bisa rekomendasikan asuransi partner
- Potential revenue: komisi referral asuransi

#### Digital Document Storage (Per Kendaraan)
- Upload dan simpan: STNK, BPKB, asuransi, faktur pembelian
- Customer bisa akses via portal
- Alert dokumen hampir expired

#### Vehicle Health Report Card
- Setiap service, sistem generate "rapor kendaraan"
- Skor kondisi per komponen (mesin, rem, ban, AC, elektrikal)
- History skor dari waktu ke waktu
- Customer bisa share link ke calon pembeli kendaraan bekas

#### VIN / Nomor Rangka Decoder
- Input nomor rangka → otomatis isi: merek, model, tahun, warna, spesifikasi mesin
- Data dari database VIN internasional
- Hemat waktu input kendaraan baru

#### Referral Program
- Customer dapat kode referral unik
- Setiap referral yang jadi transaksi → customer dapat poin/diskon
- Dashboard tracking referral per customer
- Leaderboard referrer terbaik bulan ini

#### Campaign & Blast Marketing
- Segmentasi customer: berdasarkan merek kendaraan, tanggal service terakhir, nilai transaksi, lokasi
- Kirim promo blast via WA/email ke segmen tertentu
- A/B testing pesan
- Tracking open rate dan konversi

#### Customer Satisfaction & Review
- Auto-kirim survey rating setelah service selesai (via WA)
- Rating 1-5 bintang per aspek: kecepatan, kebersihan, harga, keramahan
- Jika rating < 3 → alert langsung ke manager
- Integrasi Google Reviews (beri link langsung dari WA)
- Dashboard NPS (Net Promoter Score)

---

### 11.E SDM & PERFORMANCE

#### Absensi Teknisi
- Clock-in/clock-out via mobile (selfie + GPS)
- Rekap kehadiran bulanan
- Integrasi dengan payroll

#### KPI Teknisi Dashboard
- Jumlah jobcard selesai per hari/minggu/bulan
- Average waktu pengerjaan per jenis service
- Revenue yang dihasilkan per teknisi
- Rating dari customer per teknisi
- Target vs actual per teknisi

#### Training & Sertifikasi
- Catat sertifikasi teknisi (Toyota, Honda, Yamaha, dll)
- Tanggal expired sertifikasi
- Reminder renewal sertifikasi
- Sistem bisa rekomendasikan teknisi berdasarkan sertifikasi yang relevan dengan pekerjaan

#### Internal Chat / Komunikasi Staff
- Chat antar staff bengkel
- Group per cabang
- Broadcast dari manager ke semua staff
- Attachment foto (kirim foto kondisi kendaraan ke supervisor)

---

### 11.F TEKNOLOGI & INTEGRASI

#### OBD-II / Scanner Kendaraan
- Teknisi scan kendaraan dengan OBD-II scanner
- Hasil scan (fault codes) otomatis masuk ke jobcard
- Database DTC (Diagnostic Trouble Codes) dengan penjelasan dan rekomendasi perbaikan
- Customer lihat hasil scan di portal mereka

#### License Plate Recognition (ANPR)
- Kamera di pintu masuk membaca plat nomor
- Sistem otomatis buka data customer + kendaraan + history service
- Teknisi langsung lihat di tablet tanpa ketik manual
- Alert kendaraan yang pernah bermasalah (kredit macet, dll)

#### Digital Signage / Display Antrian
- Layar TV di ruang tunggu customer
- Tampilkan: nomor antrian yang sedang diproses, estimasi selesai
- Iklan promo bengkel bisa ditampilkan saat tidak ada antrian
- Konten bisa diupdate dari dashboard admin

#### TV Display Status Service
- Layar di area tunggu: "Kendaraan B 1234 ABC — Sedang dikerjakan — ETA: 14:30"
- Update real-time saat teknisi update status
- Nama customer bisa disamarkan (privasi)

#### Tablet Mode untuk Teknisi
- UI disederhanakan untuk layar sentuh
- Teknisi bisa: foto, isi checklist, update status, tambah parts — semua dari tablet di lapangan
- Tidak perlu balik ke komputer

#### Integrasi E-Commerce Parts
- Jual parts online via toko online bengkel
- Stok tersinkron dengan sistem bengkel
- Customer bisa order online, ambil di bengkel atau dikirim

#### GoJek/Grab Antar Jemput
- Integrasi dengan GoJek Corporate / Grab for Business
- Saat customer request antar jemput, sistem auto-order GoCar/GrabCar
- Biaya langsung ditagihkan ke invoice

---

### 11.G KEPATUHAN & REGULASI INDONESIA

#### Faktur Pajak Digital (e-Faktur)
- Generate e-Faktur sesuai format Direktorat Jenderal Pajak
- Nomor seri faktur pajak dari sistem
- Upload ke DJP OnlinePajak (atau integrasi API jika tersedia)
- Rekap PPN Keluaran per masa pajak

#### Laporan Keuangan Standar
- Neraca (Balance Sheet)
- Laporan Laba Rugi
- Arus Kas
- Format sesuai SAK EMKM (standar akuntansi UMKM Indonesia)
- Export ke Excel untuk akuntan

#### BPOM & Standar Produk (untuk bengkel yang jual oli/cairan)
- Tracking produk yang punya sertifikasi BPOM/SNI
- Alert produk yang sertifikasinya expired

---

### 11.H SAAS & MONETISASI (Jika Dijual ke Bengkel Lain)

#### Multi-Tenant Architecture
- Satu instance app bisa dipakai ratusan bengkel berbeda
- Data tiap bengkel terisolasi (per-tenant database atau schema)
- Subdomain per bengkel: `namabengkel.app.com`
- Onboarding wizard untuk bengkel baru (setup dalam 5 menit)

#### Pricing Tier / Paket Berlangganan
- **Starter**: 1 cabang, 2 user, fitur dasar — Rp 199.000/bulan
- **Pro**: 3 cabang, 10 user, semua fitur — Rp 499.000/bulan
- **Enterprise**: unlimited, custom domain, priority support — Rp 999.000/bulan
- Trial 14 hari gratis

#### Usage-Based Features
- Biaya per SMS/WA yang dikirim
- Tambahan storage untuk foto
- Tambahan user slot

#### White-Label
- Bengkel besar / jaringan bisa pakai dengan logo mereka sendiri
- Custom domain (bukan subdomain)
- Custom color scheme
- Harga enterprise

#### Marketplace Add-On
- Plugin/add-on yang bisa dibeli terpisah:
  - Modul asuransi
  - Modul fleet korporat
  - Integrasi akuntansi (Jurnal, Accurate, Zahir)
  - Modul wash bay premium
  - Advanced analytics

#### Affiliate / Referral untuk Bengkel
- Bengkel yang referensikan ke bengkel lain → dapat komisi
- Dashboard tracking komisi
- Pembayaran otomatis via transfer

---

### 11.I AI & PREDIKTIF

#### Prediksi Service Berikutnya
- Berdasarkan: jarak tempuh rata-rata per bulan + interval service → prediksi kapan service berikutnya
- Otomatis kirim reminder saat mendekati jadwal prediksi
- Akurasi meningkat seiring waktu (makin banyak data)

#### Rekomendasi Parts Otomatis
- Berdasarkan: jenis kendaraan + usia + odometer + history service → rekomendasikan parts yang sebaiknya dicek
- "Kendaraan ini belum ganti timing belt sejak 80.000 km, sudah 95.000 km sekarang"
- Teknisi tinggal approve/skip rekomendasi

#### Deteksi Anomali Harga
- Alert jika harga parts yang diinput sangat berbeda dari harga normal historis
- Mencegah fraud atau human error

#### Demand Forecasting Stok
- Prediksi berapa banyak oli merek X yang dibutuhkan bulan depan
- Berdasarkan: history pemakaian + musim + trend
- Otomatis adjust reorder point

#### Chatbot Customer Service
- WA bot yang bisa jawab pertanyaan umum customer
- Cek status service: "Mobil saya sudah selesai belum?"
- Booking service: "Saya mau booking servis hari Senin jam 9"
- Eskalasi ke staff manusia jika pertanyaan kompleks

---

### 11.J FITUR ENGAGEMENT CUSTOMER

#### Gamifikasi Service
- Customer kumpulkan "km points" setiap service
- Badge: "Pelanggan Setia 2 Tahun", "50x Servis", dll
- Leaderboard customer terbanyak transaksi
- Reward otomatis saat capai milestone

#### Program Langganan Servis (Service Subscription)
- Customer bayar flat Rp X/bulan → dapat 1x ganti oli + 1x cuci setiap bulan
- Auto-renew subscription
- Tracking penggunaan benefit per bulan
- Revenue predictable untuk bengkel

#### Vehicle Birthday Reminder
- Kirim ucapan ke customer saat anniversary kendaraan mereka (1 tahun beli)
- Sertakan voucher diskon servis
- Engagement touchpoint yang personal

#### Komunitas & Forum (opsional)
- Forum diskusi antar customer bengkel
- Tips perawatan kendaraan
- User-generated content → SEO benefit
- Bengkel bisa post tips/artikel

---

### Ringkasan Total Fitur Potensial

| Kategori | Jumlah Fitur Baru |
|---|---|
| Operasional Bengkel | 8 fitur |
| Keuangan & Pembayaran | 8 fitur |
| Inventory Lanjutan | 7 fitur |
| Pelanggan & CRM | 10 fitur |
| SDM & Performance | 4 fitur |
| Teknologi & Integrasi | 7 fitur |
| Kepatuhan Indonesia | 3 fitur |
| SaaS & Monetisasi | 6 fitur |
| AI & Prediktif | 5 fitur |
| Engagement Customer | 4 fitur |
| **TOTAL** | **62 fitur baru** |

---

---

## 12. Fitur Spesifik Bengkel Motor

> Motor punya workflow & kebutuhan berbeda dari mobil. Banyak bengkel motor besar (AHASS, YSS, FDR, bengkel custom) butuh fitur ini.

### 12.1 Servis Spesifik Motor

#### CVT Service Tracker (Motor Matic)
- Jadwal penggantian: V-belt, roller, kampas ganda, mangkok CVT
- Alert berdasarkan km: "V-belt sudah 24.000 km, rekomendasi ganti di 25.000 km"
- History penggantian CVT per kendaraan
- Foto kondisi CVT saat dibuka (before/after)

#### Rantai & Sproket Tracker
- Input km saat terakhir ganti rantai/sproket
- Alert ganti rantai tiap 15.000–20.000 km
- Cek kondisi: kendur/aus/berkarat → foto + catatan teknisi
- Rekomendasi ukuran rantai per model motor

#### Injeksi Cleaning Service
- Jadwal servis injektor tiap 10.000–15.000 km
- Rekam hasil pengukuran: tekanan bahan bakar sebelum & sesudah
- Foto injektor kotor vs bersih
- History pembersihan per motor

#### Karburator Service (Motor Lawas)
- Step-by-step checklist bongkar-pasang karbu
- Rekam hasil setelan: main jet, pilot jet, sekrup udara
- Foto kondisi karbu (kerak, karat, bensin basi)
- Riwayat setelan per kendaraan

#### Bore Up / Modifikasi Mesin
- Catat spesifikasi modifikasi: ukuran piston, stroke, head, noken as
- Sebelum-sesudah: tenaga standar vs setelah modif (dyno result)
- Garansi modifikasi (berapa km/bulan)
- Parts modif yang dipasang: merek, seri, tanggal pasang

#### Ganti Oli Gardan (Khusus Motor Matic)
- Interval berbeda dari oli mesin (tiap 10.000 km atau 6 bulan)
- Alert terpisah dari oli mesin
- Rekam volume & merek oli gardan yang dipakai

#### Ban Motor: Tubeless vs Tube
- Catat tipe ban: tubeless atau pakai ban dalam
- History penggantian ban depan/belakang terpisah
- Rekam: merek, ukuran, DOT (tanggal produksi ban)
- Alert penggantian berdasarkan km atau usia ban (max 4 tahun dari DOT)

#### Aki / Battery Motor
- Catat tanggal pasang aki dan merek
- Reminder ganti aki tiap 2 tahun
- Cek tegangan aki (bisa input manual dari voltmeter)
- Rekam tipe: aki basah vs MF (maintenance free)

#### Helm Cleaning Service (Add-on Revenue)
- Service cuci/rawat helm
- Pilihan: cuci biasa, cuci busa, poles visor, servis kunci
- Antrian helm seperti antrian kendaraan
- Notify WA saat helm selesai

---

### 12.2 Motor Custom & Modifikasi

#### Workshop Custom Build Tracker
- Project tracker per motor custom: dari rangka sampai finishing
- Milestone: rangka selesai, mesin, kelistrikan, finishing cat, final assembly
- Foto progress per tahap
- Estimasi budget vs realisasi biaya
- Portfolio (bisa ditampilkan di halaman publik bengkel)

#### Parts Custom & Aftermarket Database
- Katalog parts aftermarket: knalpot racing, suspensi, rem, lampu, dll
- Merek: Yoshimura, FDR, IRC, Swallow, DBS, Akrapovic, dll (user input sendiri)
- Kompatibilitas per model motor
- Harga beli vs harga jual
- Foto parts

#### Dyno Test Management
- Jadwal dyno test
- Rekam hasil: HP, Torsi, RPM di peak power
- Grafik power curve (upload file dari dyno machine)
- Perbandingan sebelum vs sesudah modif
- Sertifikat dyno test (PDF) yang bisa dikirim ke customer

#### Cat / Airbrush Motor
- Booking slot pengecatan
- Pilihan: solid, metalik, custom airbrush, decal wrapping
- Estimasi waktu: primer, base coat, clear coat, curing
- Foto referensi dari customer
- Approval desain sebelum eksekusi

---

## 13. Fitur Spesifik Bengkel Mobil

### 13.1 Servis Wajib Mobil

#### AC Service Tracker
- Komponen: freon, kondensor, evaporator, kompresor, expansion valve, filter kabin
- Cek tekanan freon: low side & high side (input dari manifold gauge)
- Alert penggantian filter kabin tiap 15.000 km
- Rekam tipe freon: R134a atau R1234yf
- Flush AC coolant tracking

#### Spooring & Balancing
- Catat hasil pengukuran spooring: toe, camber, caster, king pin inclination
- Nilai sebelum vs sesudah adjustment
- Rekam hasil balancing: berat timah yang ditambah per ban (gram)
- Alert spooring tiap 20.000 km atau setelah ganti ban/kena lubang parah
- Cetak hasil spooring sebagai bukti ke customer

#### Body Repair & Cat (Bodyshop Module)
- Estimasi kerusakan per panel: bumper, pintu, kap mesin, dll
- Foto kerusakan dengan anotasi (tandai area yang rusak)
- Pilihan repair: ketok biasa, PDR (Paintless Dent Repair), cat ulang
- Tracking tahap: bongkar → dempul → primer → cat dasar → cat warna → clear coat → poles
- Integrasi dengan asuransi: foto + estimasi langsung ke portal surveyor

#### Kaca Film (Window Tint)
- Rekam tipe kaca film per jendela: merek, VLT%, UV rejection%
- Garansi per pasang: 1-5 tahun tergantung kualitas
- Alert garansi hampir habis
- Foto sebelum vs sesudah pemasangan

#### Audio & Aksesori Instalasi
- Rekam pemasangan: head unit, speaker, subwoofer, amplifier, dashcam, alarm
- Serial number per perangkat yang dipasang
- Garansi instalasi
- Wiring diagram tersimpan per mobil (foto)

#### Detailing & Poles
- Paket detailing: wash, wax, compound, ceramic coating, nano coating
- Jadwal ulang detailing (ceramic coating: 1-2 tahun sekali)
- Before/after foto wajib
- Rekam produk yang dipakai (merek, batch number untuk garansi)

#### Suspensi & Kaki-Kaki
- Checklist: shock absorber, per/pegas, ball joint, tie rod, long tie rod, rack steer
- Rekam kondisi: normal, perlu perhatian, segera ganti
- Alert ganti shockbreaker tiap 50.000 km
- Foto komponen yang diganti

#### Transmisi Otomatis Service
- Ganti ATF (Automatic Transmission Fluid) tiap 40.000–60.000 km
- Rekam tipe ATF: Dexron III, Dexron VI, Toyota WS, dll
- Volume ATF yang dipakai
- Cek kondisi: normal, ada metal filings, terbakar (warna ATF)
- Alert CVT flush untuk mobil CVT

#### Timing Belt / Timing Chain
- Alert penggantian timing belt tiap 60.000–100.000 km (tergantung pabrikan)
- Rekam komponen ikutan: water pump, tensioner, idler
- History penggantian
- PENTING: Keterlambatan ganti timing belt = mesin jebol → fitur ini bisa mencegah klaim warranty besar

#### Rem Lengkap
- Ketebalan kampas rem depan & belakang (input dalam mm)
- Alert ganti kampas jika < 3mm
- Kondisi cakram/drum: normal, perlu bubut, perlu ganti
- History penggantian per axle
- Rekam merek kampas: OEM, aftermarket (Bendix, Akebono, dll)

---

### 13.2 Fitur Khusus Mobil

#### OBD-II Diagnostic Integration
- Baca fault code (DTC) via adapter Bluetooth/WiFi
- Database 15.000+ kode DTC dengan penjelasan Indonesia
- Rekam kode yang ditemukan per service
- Clear code setelah perbaikan, rekam
- History DTC per kendaraan

#### Recall Kendaraan
- Database recall resmi dari pabrikan (Toyota, Honda, dll)
- Cek apakah kendaraan customer masuk daftar recall berdasarkan VIN/nomor rangka
- Notifikasi otomatis ke customer jika kendaraannya kena recall
- Tracking apakah recall sudah ditangani

#### Flood/Banjir Assessment
- Checklist khusus untuk kendaraan kena banjir
- Poin cek: ECU, alternator, starter, karpet, interior, rem
- Estimasi kerusakan banjir
- Foto per komponen
- Laporan assessment untuk klaim asuransi banjir

#### Interior Cleaning & Restoration
- Layanan: shampo karpet, cuci plafon, poles dashboard, restorasi jok kulit
- Foto before/after per area interior
- Paket: Basic, Premium, Full Restoration
- Waktu pengerjaan: estimasi per paket

---

## 14. Fitur Kendaraan Niaga & Berat

### 14.1 Truk & Bus

#### Maintenance Berbasis Jam Operasi
- Kendaraan niaga service berdasarkan jam operasi, bukan hanya km
- Input jam operasi dari jam mesin (hourmeter)
- Interval service: tiap 250 jam, 500 jam, 1.000 jam
- Alert dual trigger: km ATAU jam operasi, mana yang duluan tercapai

#### Tire Management (Manajemen Ban Truk)
- Truk punya 6–18 ban, tiap ban perlu di-tracking individual
- Posisi ban: steer kiri/kanan, drive kiri luar/dalam, trailer
- Rekam: merek, ukuran, DOT, km saat pasang
- Alert rotasi ban (rata-kan keausan)
- Kedalaman alur ban (tread depth) per posisi
- Ban retread tracking (ban yang sudah di-vulkanisir ulang)

#### Log Book Digital Kendaraan Niaga
- Wajib diisi pengemudi sebelum keberangkatan: kondisi rem, ban, lampu, oli
- Rekam km awal & km akhir per trip
- Foto kondisi kendaraan per shift
- Digital signature pengemudi

#### Kelayakan Kendaraan (KIR / Uji Berkala)
- Jadwal uji KIR per kendaraan niaga (6 bulan sekali)
- Reminder H-30 sebelum jatuh tempo
- Upload dokumen hasil KIR
- Tracking: lulus/tidak lulus + catatan penguji
- Kendaraan yang KIR mati otomatis flagged "tidak boleh beroperasi"

#### Tachograph / Jam Mengemudi
- Tracking jam mengemudi per pengemudi (regulasi: max 8 jam/hari)
- Rekap kumulatif per minggu
- Alert jika ada pengemudi yang overwork
- Penting untuk armada perusahaan logistik

---

### 14.2 Alat Berat

#### Maintenance Berbasis Jam Mesin (Hourmeter)
- Excavator, bulldozer, forklift: service tiap 250/500/1000 jam
- Input jam dari hourmeter setiap kunjungan
- Prediksi kapan service berikutnya
- History maintenance per unit alat berat

#### Hydraulic System Service
- Ganti hydraulic oil per jam operasi
- Cek tekanan hydraulic (bar)
- Filter hydraulic tracking
- Kebocoran hydraulic: lokasi, severity, tanggal
- Suku cadang hydraulic: silinder, seal, pompa

#### Komponen Undercarriage (Track/Crawler)
- Track shoe: kondisi, ketebalan (mm), persentase sisa
- Sprocket, idler, roller: kondisi
- Alert penggantian berdasarkan jam operasi
- Foto kondisi undercarriage per inspeksi

---

## 15. Fitur Kendaraan Listrik (EV & Hybrid)

> Segmen EV tumbuh pesat. Bengkel yang siap servis EV akan punya keunggulan kompetitif besar di 2026+.

### 15.1 Battery Management

#### State of Health (SoH) Battery
- Rekam kapasitas baterai saat diperiksa: kWh tersisa vs kapasitas awal
- Persentase degradasi baterai
- Grafik penurunan SoH dari waktu ke waktu
- Rekomendasi: baterai masih oke / perlu balancing sel / perlu ganti

#### Battery Cell Balancing
- Rekam tegangan per sel (atau per modul)
- Identifikasi sel yang weak/rusak
- History balancing
- Biaya balancing vs ganti baterai: rekomendasi ke customer

#### Charging Station Management
- Bengkel punya charger stasioner untuk EV customer
- Booking slot charging
- Rekam kWh yang dicharge per sesi
- Billing otomatis: tarif per kWh
- Laporan penggunaan charger per bulan

### 15.2 Servis Spesifik EV

#### High Voltage Safety Protocol
- Checklist wajib sebelum teknisi sentuh komponen HV
- Rekam: PPE dipakai, HV diaktifkan/dinonaktifkan oleh siapa
- Digital sign-off oleh teknisi bersertifikat HV
- Insiden HV: laporan keselamatan

#### Software Update / OTA Tracking
- Rekam versi software ECU kendaraan EV
- Catat kapan update dilakukan & versi terbaru
- Beberapa update memperbaiki efisiensi/keamanan — penting didokumentasi
- Alert jika ada update kritis dari pabrikan

#### Regenerative Brake Check
- Efisiensi rem regeneratif: berapa % energi yang di-recover
- Kondisi kampas rem (EV lebih jarang ganti kampas tapi tetap perlu cek)

---

## 16. Fitur Workshop & Fasilitas Bengkel

### 16.1 Manajemen Fasilitas & Peralatan

#### Tool & Equipment Management
- Inventaris semua alat: kunci torsi, scanner, dongkrak, compressor, lift, dll
- Serial number & tanggal beli per alat
- Jadwal kalibrasi (kunci torsi wajib kalibrasi berkala)
- Peminjaman alat: siapa pinjam, kapan dikembalikan
- Perawatan preventif alat: service compressor, oli lift hidrolik, dll
- Alert garansi alat hampir habis

#### Lift / Hoist Management
- Setiap lift punya ID
- Status real-time: kosong, terpakai (jobcard no berapa)
- Kapasitas max per lift (jangan overload)
- Jadwal inspeksi lift (wajib 6 bulan sekali untuk keselamatan)
- Rekam insiden/kerusakan lift

#### Bay Management (Denah Workshop)
- Visual denah workshop: bay 1, bay 2, cuci, spooring, dyno, dll
- Status tiap bay: kosong (hijau), terpakai (merah), reserved (kuning)
- Drag-drop assign kendaraan ke bay
- Tampil di layar monitor besar di bengkel
- Historis penggunaan bay per hari (analytics)

#### Compressor & Pneumatic Tool
- Rekam tekanan operasi compressor
- Jadwal drain moisture compressor (setiap hari)
- Jadwal ganti filter udara compressor
- Alert jika tekanan drop abnormal

### 16.2 Keselamatan Kerja (K3)

#### Incident & Accident Report
- Laporan kecelakaan kerja digital
- Foto kejadian
- Penanganan: P3K, rujuk dokter, rawat inap
- Root cause analysis
- Corrective action tracking
- Laporan bulanan K3

#### Chemical & B3 Hazard Management
- Daftar bahan berbahaya di bengkel: oli bekas, bensin, cat, thinner, aki bekas
- MSDS (Material Safety Data Sheet) per bahan
- Storage compliance: penyimpanan terpisah, labeling
- Disposal tracking: bekerja sama dengan vendor B3
- Rekap volume B3 per bulan untuk pelaporan

#### PPE (Alat Pelindung Diri) Tracking
- Stok APD: sarung tangan, kacamata, sepatu safety, earplug, masker
- Distribusi APD ke teknisi
- Jadwal penggantian APD
- Training K3 per teknisi: tanggal training, materi, sertifikat

#### Work Permit System
- Pekerjaan berisiko tinggi butuh izin: kerja di ketinggian, listrik HV, las
- Digital work permit: request → approve supervisor → execute → close
- Rekam kondisi sebelum & sesudah pekerjaan
- Wajib ada pada bengkel yang sudah ISO 45001

---

## 17. Fitur Komunitas & Ekosistem

### 17.1 Bengkel Resmi / Authorized

#### Dealer Integration
- Bengkel AHASS/YSS/bengkel resmi bisa sinkron data dengan sistem dealer
- Klaim garansi langsung dari sistem bengkel ke portal dealer
- Spare parts order ke main dealer otomatis
- Rekap penjualan jasa & parts untuk laporan ke dealer
- Training tracker: teknisi sudah mengikuti training apa dari dealer

#### Warranty Claim ke Pabrikan
- Kendaraan masih dalam garansi → workflow klaim ke pabrikan
- Upload bukti: foto, diagnosis, data kendaraan
- Tracking status klaim: submitted, under review, approved, rejected
- Reimbursement tracking

### 17.2 Komunitas Otomotif

#### Club / Komunitas Member
- Customer bisa daftar sebagai member komunitas (Honda Community, Toyota Club, dll)
- Diskon khusus member komunitas
- Event komunitas: kopdar, touring, track day
- Broadcast info event ke member via WA

#### Referral Antar Bengkel (Bengkel Network)
- Bengkel A tidak bisa handle pekerjaan tertentu → refer ke Bengkel B
- Tracking referral: berapa revenue yang dihasilkan dari referral
- Komisi antar bengkel
- Rating antar bengkel dalam network

### 17.3 Marketplace Parts

#### Order Parts dari Supplier Online
- Integrasi dengan marketplace spare parts (format API-based, bukan hardcode ke satu platform)
- Cari parts by nomor OEM / kode parts
- Bandingkan harga dari beberapa supplier
- Order langsung dari sistem, auto-create purchase record
- Tracking pengiriman

#### Jual Parts Bekas / Core Return
- Parts yang dilepas dari kendaraan kadang masih punya nilai (core return)
- Sistem catat parts lama yang dilepas: kondisi, estimasi nilai
- Jual ke supplier sebagai core return → kurangi harga beli parts baru
- Atau jual ke customer lain sebagai parts refurbished

---

## 18. Fitur Pelaporan Lanjutan

### 18.1 Business Intelligence

#### Dashboard Eksekutif (Owner View)
- Revenue hari ini vs kemarin vs minggu lalu
- Gross profit margin real-time
- Top 5 service terlaris bulan ini
- Top 5 customer berdasarkan revenue
- Teknisi dengan produktivitas tertinggi
- Parts dengan margin tertinggi
- Kendaraan yang paling sering masuk bengkel
- Trend bulanan: naik/turun berapa %

#### Cohort Analysis Customer
- Customer yang pertama kali service bulan X → berapa % masih service 3 bulan kemudian?
- Retention rate per bulan
- Churn rate: customer yang tidak balik setelah N hari
- LTV (Lifetime Value) per customer

#### Break-Even & Target Analysis
- Input biaya tetap bengkel per bulan (sewa, listrik, gaji, dll)
- Sistem hitung berapa revenue minimum harus dicapai setiap bulan
- Progress harian: "sudah X% dari target break-even bulan ini"
- Alert jika target tidak tercapai minggu ini

#### Laporan Perbandingan Cabang
- Side-by-side comparison semua cabang
- Metric: revenue, jumlah jobcard, average service value, customer baru
- Ranking cabang berdasarkan performance
- Identifikasi cabang yang underperform

### 18.2 Export & Integrasi Akuntansi

#### Export ke Software Akuntansi
- Export jurnal harian ke: Jurnal.id, Accurate Online, Zahir, format CSV/Excel universal
- Mapping akun: kas, piutang, pendapatan jasa, HPP parts, utang supplier
- Rekonsiliasi otomatis: total invoice di sistem = total di software akuntansi

#### Laporan untuk Perbankan
- Rekap mutasi kas & bank per bulan (untuk pengajuan kredit ke bank)
- Laporan keuangan sederhana: laba rugi, neraca (format Bank Indonesia)
- Track record omzet 12 bulan terakhir dalam satu halaman

---

## 19. Fitur Mobile App (Customer & Teknisi)

### 19.1 Customer Mobile App

#### Real-Time Service Tracking
- Customer buka app → lihat status kendaraan mereka
- Timeline: masuk → inspeksi → dikerjakan → QC → selesai → bayar → keluar
- Foto progress dikirim real-time (teknisi foto → langsung muncul di app customer)
- Notifikasi push setiap perubahan status

#### Approve Pekerjaan Tambahan dari HP
- Teknisi temukan masalah tambahan → kirim estimasi ke customer via app
- Customer bisa approve atau tolak dari HP
- Chat langsung dengan service advisor
- Tidak perlu telepon-teleponan lagi

#### Riwayat Kendaraan Lengkap
- Semua history service ada di satu tempat
- Filter per kendaraan
- Download invoice dalam PDF
- Lihat foto before/after dari service sebelumnya

#### Booking Service Online
- Pilih tanggal & jam dari app
- Pilih jenis service
- Estimasi biaya sebelum datang
- Reminder H-1 sebelum jadwal

### 19.2 Teknisi Mobile App

#### Jobcard di Genggaman
- Lihat semua jobcard yang di-assign ke dia
- Update status dari HP/tablet
- Foto langsung dari kamera → masuk ke jobcard
- Checklist digital per langkah service

#### Parts Request dari Lapangan
- Teknisi butuh parts → request dari app
- Gudang approve & siapkan parts
- Notifikasi ke teknisi saat parts sudah siap ambil
- Tidak perlu bolak-balik ke kasir/gudang

#### Time Tracking per Pekerjaan
- Start timer saat mulai kerjakan → stop saat selesai
- Otomatis hitung biaya jasa berdasarkan jam
- Rekap waktu kerja per hari untuk payroll

---

## 20. Fitur Masa Depan (Futuristik tapi Realistis 2027+)

### 20.1 AI Lanjutan

#### Computer Vision untuk Damage Detection
- Customer upload foto kendaraan → AI deteksi kerusakan otomatis
- Highlight area rusak dengan bounding box
- Estimasi biaya perbaikan awal
- Berguna untuk booking service online & klaim asuransi

#### Predictive Failure Analysis
- Berdasarkan data 10.000+ kendaraan serupa → prediksi komponen mana yang kemungkinan akan gagal
- "Kendaraan dengan profil ini biasanya butuh ganti alternator di km 120.000"
- Berbasis machine learning dari data historis bengkel

#### Voice Command untuk Teknisi
- Teknisi bicara ke headset → sistem mencatat pekerjaan
- "Ganti oli mesin Shell Helix 10W-40 1 liter" → otomatis masuk ke jobcard
- Tangan teknisi tetap bisa kerja, tidak perlu ketik

### 20.2 IoT & Konektivitas

#### Telematics Integration (GPS + OBD Real-Time)
- Customer pasang perangkat telematics di kendaraan
- Data mengalir real-time: lokasi, kecepatan, kondisi mesin, konsumsi BBM
- Sistem auto-detect jika ada fault code → kirim notifikasi ke customer & bengkel
- Bengkel bisa lihat "kesehatan" semua kendaraan customer secara real-time
- Auto-schedule service berdasarkan data aktual (bukan estimasi)

#### Smart Bay Sensor
- Sensor di setiap bay mendeteksi apakah ada kendaraan
- Otomatis update status bay di sistem
- Tidak perlu manual update "bay ini sedang terpakai"

#### Kamera ANPR (Automatic Number Plate Recognition)
- Kamera di pintu masuk baca plat otomatis
- Sistem buka data kendaraan & customer dalam 2 detik
- Catat waktu masuk/keluar otomatis
- Alert kendaraan blacklist (piutang macet, kendaraan curian)

---

## 21. Ringkasan Master Fitur (Semua Kategori)

| No | Kategori | Jumlah Fitur |
|---|---|---|
| 1 | Fitur Dasar (dari Garage Master) | 24 modul |
| 2 | Fitur Kurang — Prioritas Tinggi | 7 fitur |
| 3 | Fitur Kurang — Prioritas Menengah | 8 fitur |
| 4 | Fitur Kurang — Prioritas Rendah | 10 fitur |
| 5 | Extended — Operasional & Keuangan | 16 fitur |
| 6 | Extended — CRM & Marketing | 10 fitur |
| 7 | Extended — SaaS & AI | 11 fitur |
| 8 | **Spesifik Motor** | **14 fitur** |
| 9 | **Spesifik Mobil** | **12 fitur** |
| 10 | **Kendaraan Niaga & Berat** | **8 fitur** |
| 11 | **Kendaraan Listrik (EV/Hybrid)** | **7 fitur** |
| 12 | **Workshop & Fasilitas (K3)** | **10 fitur** |
| 13 | **Komunitas & Ekosistem** | **6 fitur** |
| 14 | **Pelaporan Lanjutan & BI** | **8 fitur** |
| 15 | **Mobile App (Customer & Teknisi)** | **8 fitur** |
| 16 | **Futuristik (AI, IoT, Vision)** | **6 fitur** |
| **TOTAL** | | **~165 fitur** |

---

## 22. Prioritas Build — Roadmap Realistis

### Phase 1 — MVP (3 bulan)
Core operations yang menghasilkan uang hari 1:
- Auth + RBAC
- Customer + Kendaraan
- Service / Jobcard (mobil & motor)
- Invoice + QRIS payment
- Inventory + Purchase
- WhatsApp notification
- Dashboard owner

### Phase 2 — Growth (3 bulan berikutnya)
Fitur yang meningkatkan retensi & revenue:
- Customer portal + mobile web
- STNK/KIR/Asuransi reminder
- Loyalty poin
- Spooring & balancing modul
- AC service tracker
- CVT service tracker
- Reports lengkap + export Excel

### Phase 3 — Scale (6 bulan berikutnya)
Untuk bisnis bengkel yang sudah serius:
- Multi-cabang
- Payroll teknisi
- KPI dashboard
- Marketing blast
- Fleet management
- Multi-tenant SaaS
- Mobile app (customer)
- OBD-II integration

### Phase 4 — Future (1 tahun+)
Keunggulan kompetitif jangka panjang:
- EV service module
- AI predictive maintenance
- Computer vision damage detection
- Telematics integration
- Marketplace parts
- Integrasi akuntansi

---

*File ini dibuat sebagai referensi untuk membangun aplikasi bengkel baru yang lebih baik dari Garage Master.*
