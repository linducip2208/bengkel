# Bengkel Paten — Roadmap & Progress Tracker

**Status:** ERP Bengkel v1.0 + Retail Module
**Last update:** 2026-05-20

File ini **bukan untuk end-user**. Untuk tracking internal developer & owner aplikasi.
Doc untuk user → buka `/docs` di aplikasi.

---

## ✅ Sudah Dibangun (Production Ready)

### Core ERP Bengkel
- [x] **Auth** — login/logout dengan default credentials seeded
- [x] **Dashboard** — ringkasan service hari ini, revenue, outstanding invoice, stok rendah
- [x] **Customer** — CRUD + import CSV + histori service per customer
- [x] **Vehicle** — CRUD + multi-image upload + histori service per kendaraan + nomor plat unik
- [x] **Service** — Buat service, jobcard auto-generate, complete, upload foto before/after
- [x] **Jobcard** — Detail kerjaan teknisi + print PDF + gate pass PDF
- [x] **Observation Checklist** — Inspeksi point per kategori, save per service
- [x] **Gate Pass** — Surat jalan keluar dengan timestamps + print PDF
- [x] **Washbay** — Kanban-style slot service (kosong/dipakai/maintenance) + occupy/release

### Inventory & Procurement
- [x] **Product** — CRUD + import CSV + harga jual/beli + warranty
- [x] **Stock Record** — Tracking qty per produk + minimum stock + rack location
- [x] **Stock Opname** — Koreksi stok manual setelah hitung fisik
- [x] **Stock Adjust** — Adjustment per produk
- [x] **Stock History** — Audit trail otomatis setiap pergerakan stok
- [x] **Supplier** — CRUD + histori PO per supplier
- [x] **Purchase (PO)** — Buat PO + mark received + auto-update stock + history records

### Sales & POS
- [x] **Sales (Kendaraan)** — Catat penjualan kendaraan bekas + DP + invoice link
- [x] **POS Kasir** ⭐ — Cart-style retail POS untuk sparepart
  - Session open/close dengan saldo awal & akhir
  - Search/scan barcode produk
  - Multi-payment method
  - Diskon per item & total
  - Auto-deduct stok + audit trail
  - Auto-generate invoice prefix `POS-YYYYMMDD-XXXX`
  - Struk thermal 58/80mm dengan auto-print dialog
  - Cash reconcile (saldo expected vs aktual)

### Finance
- [x] **Invoice** — Generate dari service/sale/POS + PDF + Email + WhatsApp
- [x] **Payment** — Partial payment (cicil) + full + history records + log
- [x] **Income** — Pemasukan non-operasional + history
- [x] **Expense** — Pengeluaran + history
- [x] **Reports** — 4 jenis (service, sales, stock, financial) + export PDF & Excel

### Multi-Branch & Master Data
- [x] **Branch** — CRUD + activation + branch switcher di topbar
- [x] **BusinessHour** — Set jam buka per hari per cabang
- [x] **Holiday** — Hari libur per cabang atau global, recurring tahunan
- [x] **Branch Scoping** — Global scope otomatis filter data by branch session
- [x] **13 Master Data** — Vehicle types, brands, fuel, colors, product types/units, payment methods, tax rates, repair categories, observation types, observation points, inspection points library, checkout categories

### Geografi & Currency
- [x] **Country / State / City** — Hierarchical CRUD
- [x] **Multi-Currency** — Currency CRUD + default + format helper + @money directive
- [x] **Currency in Invoice PDF, email, dashboard, reports** — `@money()` directive

### Notification & Reminder
- [x] **Notification Template** — Template dengan variable {customer_name}, {plate}, dst
- [x] **Reminder** — Schedule reminder + send single + batch
- [x] **WhatsApp** — Adapter: Fonnte, Wablas, WA Business API (configurable)
- [x] **Email** — Mail::send + attachment PDF (invoice via email)
- [x] **Send via WA link** — `wa.me` redirect untuk kirim invoice instant

### HRM (Komisi Teknisi)
- [x] **Commission Per Service** ⭐ — Per-technician commission_pct + commission_amt
- [x] **Mark Paid** — Per-baris atau batch dengan checkbox
- [x] **Laporan Komisi** — Per teknisi per periode, breakdown paid/unpaid
- [x] **Audit** — paid_by tracked (siapa admin yang approve)

### Audit & Compliance
- [x] **Stock History viewer** — Filter by produk/tipe/tanggal
- [x] **Email Log viewer** — Status sent/failed + detail per log
- [x] **Notes polymorphic** — Catatan internal di customer/vehicle/service/invoice
- [x] **Soft delete** — Semua model utama pakai SoftDeletes
- [x] **FK guard** — destroy() cek referensi dulu sebelum delete master data

### Extensibility
- [x] **Custom Fields** — Field extensibility per modul (text/number/date/select/boolean/textarea)

### Integration & License
- [x] **License v3 Pairing** — whitelabel.co.id RSA + AES-GCM, RequirePair middleware
- [x] **API REST v1** — Sanctum-protected: customer, vehicle, service, invoice, product, purchase, sale, income, expense, dashboard, report — untuk Flutter app

### Public-Facing & SEO
- [x] **Public Docs** `/docs` — Tutorial lengkap untuk end-user (19 chapter)
- [x] **Popup Support** — WhatsApp 081296052010 floating popup di docs (vibrant gradient, sekali tampil)
- [x] **Programmatic SEO** — `/best/{category}`, `/alternatives-to/{slug}`, `/compare/{a}-vs-{b}`
- [x] **Sitemap.xml** dynamic
- [x] **JSON-LD schema** per page

### Quality / Robustness
- [x] **No raw JSON leak** — Semua master-data controller render view (tidak return Eloquent collection)
- [x] **Unique scoped to soft-delete** — `Rule::unique()->whereNull('deleted_at')`
- [x] **FK destroy guard bypass branch scope** — `withoutGlobalScopes()` di FK check
- [x] **Topbar Branch query safe** — try/catch fallback ke empty collection
- [x] **Action button visibility** — Bootstrap Icons CDN + CSS enhancement (pastel bg, tooltip hover, larger size)
- [x] **Currency::default() in-memory cache** — fix `__PHP_Incomplete_Class` from DB cache driver

---

## 🚧 Backlog Prioritas Tinggi

### Marketing & Retention
- [x] **Loyalty Points** ⭐ — Poin per transaksi + tier (bronze/silver/gold/platinum) + adjust manual + histori + auto-expire weekly
- [x] **Voucher / Promo Code** ⭐ — CRUD (percent/fixed, min purchase, max discount, limit, expiry, active) + AJAX validate endpoint
- [ ] **Birthday auto-greeting** — Cron daily, kirim WA/email customer yang ulang tahun + diskon
- [ ] **Referral program** — Customer ajak customer dapat bonus poin

### Customer Self-Service
- [x] **Online Booking Customer** ⭐ — Form publik `/booking` no-login + admin panel `/bookings` workflow pending→confirmed→in_progress→done→cancelled + badge notif
- [x] **Customer Portal** ⭐ — Login by HP `/customer/login` (default = 6 digit terakhir HP) + dashboard invoice & service + ganti password
- [ ] **Tracking status service real-time** — "Mobil saya lagi di tahap apa?" via URL link
- [ ] **Approval estimate via link** — Setujui RAB lewat WA link (one-click approve)
- [ ] **Review / rating publik** — Trustpilot-style per service

### Payment & Finance
- [ ] **Payment Gateway Online** — Midtrans/Xendit/Doku → bayar via link + webhook notification
- [ ] **E-Faktur DJP Coretax** — Faktur pajak standar untuk B2B
- [ ] **PPh 21 / PPh 23 calculation** — Pajak income/komisi karyawan
- [ ] **Petty cash management** — Kas kecil dengan replenishment

### Security & Operational
- [ ] **2FA Authentication** — Google Authenticator atau SMS OTP untuk admin login
- [ ] **Role / Permission UI** — Spatie installed, butuh UI manage role & assign permission
- [ ] **Activity Log per user** — Audit siapa edit apa kapan
- [x] **Backup Otomatis** ⭐ — Artisan `backup:db --keep=14` (mysqldump + rotate) + scheduled daily 02:00 di `routes/console.php`
- [ ] **IP whitelist / device binding** — Restrict admin login per IP/device

### Workshop Operations
- [ ] **Inspection PDF + Foto** — PDF observasi dengan foto before/after embedded side-by-side
- [ ] **Quality check workflow** — Inspector approve sebelum gate pass keluar
- [ ] **Complaint handling / klaim warranty** — Track komplain + claim flow
- [ ] **Warranty tracking per parts** — Kapan exp + claim auto-detect
- [ ] **Cost estimation calculator** — Estimasi cepat sebelum diagnosis penuh

### Mobile
- [ ] **Mobile App Teknisi (Flutter)** — Assign job, foto progress, customer signature digital
- [ ] **Mobile App Customer** — Booking, status service, invoice di HP
- [ ] **Push notification (Firebase)** — Notif "service ready" / "invoice issued"
- [ ] **QR code di gate pass** — Scan untuk verifikasi keluar

---

## 🔮 Roadmap Lanjutan (Future Phase)

### HRM Full
- [ ] Absensi teknisi (clock in/out + GPS)
- [ ] Gaji bulanan + slip gaji PDF
- [ ] KPI teknisi dashboard
- [ ] Schedule shift teknisi (pagi/siang/malam)
- [ ] Training & sertifikasi register
- [ ] Leave / cuti management

### Marketing Advanced
- [ ] Email campaign (newsletter, promo blast)
- [ ] Customer segmentation (RFM analysis)
- [ ] WhatsApp Business API native (bukan via Fonnte/Wablas)
- [ ] Live chat widget di website public

### Accounting Full
- [ ] Jurnal akuntansi (debit/kredit) per transaksi
- [ ] Neraca + Laba Rugi formal (bukan cuma cashflow)
- [ ] Sub-ledger per akun
- [ ] Export ke Accurate / Jurnal / Mokas

### BI & Analytics
- [ ] Dashboard BI chart interaktif (drill-down)
- [ ] Profit margin per service category
- [ ] Forecasting revenue & demand AI
- [ ] Customer LTV (Lifetime Value)
- [ ] Mechanic utilization rate (% waktu produktif)
- [ ] Sparepart velocity analysis (fast/slow movers)
- [ ] Anomaly detection (revenue drop alert)

### Multi-tenant SaaS
- [ ] 1 instance untuk banyak bengkel
- [ ] Subscription billing per tenant (Midtrans recurring)
- [ ] Tenant onboarding wizard self-signup
- [ ] Super admin (lihat semua tenant)
- [ ] Data isolation per tenant (schema/row level)

### AI Features
- [ ] AI diagnose dari keluhan customer (free text → suggest probable cause)
- [ ] AI demand forecast sparepart
- [ ] AI image recognition foto kerusakan → estimasi otomatis
- [ ] Chatbot AI customer (first-response otomatis)
- [ ] Smart pricing recommendation

### Workshop Advanced
- [ ] Sub-contractor management (outsource job: cat, body repair)
- [ ] Tools & equipment register
- [ ] Maintenance schedule untuk equipment (service lift, kompresor)
- [ ] Recall management (parts/kendaraan yang di-recall pabrik)
- [ ] Multi-step approval workflow (estimasi → approve customer → kerjakan)

### Integration
- [ ] Google Calendar sync (service jadwal ↔ Google Cal)
- [ ] Google Maps for delivery route optimization
- [ ] WhatsApp Business API native (verifikasi Meta)
- [ ] Accounting eksternal integration (Accurate/Mokas/Jurnal)
- [ ] SMS gateway native (selain via Twilio adapter)

---

## 📊 Statistik Internal

- **Total fitur built**: 80+ items terbagi di 14 kategori
- **Total fitur backlog**: 30+ items
- **Total roadmap future**: 40+ items
- **Total controllers**: 60+ (Tenant: 40+, Api: 14, root: 8)
- **Total models**: 55+
- **Total view files**: 200+
- **Total routes**: 368+ (web + api combined)
- **Total migrations**: 60+ files

## 🎯 Posisi di Marketplace

**Bengkel Paten** positioned sebagai:
- **Vertical ERP** khusus automotive aftermarket
- **All-in-one** workshop management + retail POS
- **Multi-cabang ready** dengan auto-scoping
- **White-label ready** via license v3 pairing
- **Mobile-ready** via API REST untuk Flutter

**Target market:**
- Bengkel skala medium (10-50 service/hari)
- Bengkel rantai / franchise (multi-cabang)
- Authorized service center
- Dealer dengan after-sales department
- Bengkel + retail sparepart hybrid

**Tidak ideal untuk:**
- Bengkel 1-2 orang (overkill setup)
- Pure retail sparepart counter (use POS-only software)

---

## 📞 Contact

Support: WhatsApp **081296052010**
Marketplace: https://whitelabel.co.id
Default credentials: `admin@bengkelpaten.id` / `password` (wajib ganti sebelum production)
