# Flutter API Contract — Bengkel Paten (Laravel backend)

> Single source of truth: Laravel. Dokumen ini cukup untuk membangun
> aplikasi Flutter tanpa menebak-nebak API. Jika ada konflik antara
> dokumen ini dan kode, **kode yang menang** — perbarui dokumen ini.

- Base URL: `{APP_URL}/api/v1` (contoh: `https://bengkel.example.com/api/v1`)
- Health: `GET {APP_URL}/up` (Laravel, tanpa auth) dan `GET /api/v1/health` (JSON, tanpa auth:
  `{status: ok|degraded|error, checks: {database, cache, queue, failed_jobs?}}`)
- Format: JSON saja. Request body: JSON (`Content-Type: application/json`).
- Bahasa pesan error: Indonesia (fallback Inggris).
- Timezone server: `Asia/Jakarta`. Tanggal kirim sebagai `YYYY-MM-DD`, datetime ISO-8601.

## 1. Authentication

Sanctum personal access token (Bearer). Tidak ada registrasi publik via API.

```
POST /api/v1/login        throttle 10/menit
  body: { "email": "...", "password": "..." }
  200:  { "user": { ...roles, permissions }, "token": "1|xxx" }
  401:  { "message": "Invalid credentials" }   // juga jika is_active = false

GET  /api/v1/me           -> user saat ini (butuh token)
POST /api/v1/logout       -> mencabut token saat ini saja
```

- Header: `Authorization: Bearer <token>`
- Rate limit grup auth: `throttle:120,1` (120 req/menit).
- Umur token: `SANCTUM_TOKEN_EXPIRATION` (menit, default env `57600` = 40 hari).
  Setelah expired/revoke: `401 Unauthorized`.
- Guard `web` dan `sanctum` tidak tertukar: role dibaca eksplisit dari guard `web`.

## 2. Bentuk response

Koleksi (paginated, Laravel paginator — apa adanya):

```json
{
  "current_page": 1, "data": [ ... ], "per_page": 20,
  "total": 42, "last_page": 3, "next_page_url": "...", "prev_page_url": null
}
```

Detail object: `{ "data": { ... } }`.

Aksi: `{ "message": "...", "data": { ... } }` dengan status yang benar
(`200` ok, `201` created, `422` validasi/bisnis, `401`, `403`, `404`).

Error:

```json
// 422 validasi
{ "message": "The ... field is required.", "errors": { "field": ["..."] } }
// 422 aturan bisnis (saldo, status ilegal, stok ...)
{ "message": "Task tidak bisa dimulai dari status completed." }
// 404 (route-model binding lintas cabang = 404, bukan 403 — anti enumerasi)
{ "success": false, "message": "Data tidak ditemukan." }
```

## 3. Pagination / filter / sort

- Semua `GET index`: `?per_page=N` (default 20), `?page=N`.
- Filter umum: `?status=`, `?service_id=`, `?customer_id=`, `?date_from=&date_to=` (lihat per endpoint).
- Pencarian: `customers?search=`, `vehicles?search=`, `products?search=`, `technicians?search=`.
- Sorting server-side konsisten: `latest()` (belum ada parameter `sort` generik).

## 4. Endpoint (110 routes, prefix `/api/v1`)

| Domain | Method + Path | Role tulis |
|---|---|---|
| Auth | `POST /login`, `POST /logout`, `GET /me` | — |
| Health | `GET /health` (publik) | — |
| Dashboard | `GET /dashboard/stats` | semua staff |
| Master data | `GET /master-data` | semua staff |
| Branches | `GET /branches` | semua staff (hanya cabang yang boleh diakses) |
| Notifications | `GET /notifications` | semua staff (milik sendiri) |
| Customers | CRUD `/customers` | semua staff login (baca+tulis) |
| Vehicles | CRUD `/vehicles` | semua staff login |
| Services/Jobcards | `GET /services`, `GET /services/{id}`, `POST /services/{id}/complete` | complete: admin/manager/service_advisor |
| Jobcards | CRUD `/jobcards` + `/complete` | tulis: admin/manager/service_advisor |
| Bookings | CRUD `/bookings` + `POST /bookings/{id}/convert` | tulis: admin/manager/service_advisor |
| Estimates | `GET /estimates`, `GET /estimates/{id}` | semua staff |
| Estimates | `POST /estimates/{id}/approve`, `/reject`, `/decide` | admin/manager/service_advisor |
| Estimates | `POST /estimates/{id}/convert` (→ invoice) | admin/manager |
| Work packages | `GET /work-packages`, `GET /work-packages/{id}` | semua staff |
| QC | `POST /work-packages/{id}/qc` `{result: passed\|failed, notes?, task_id?}` | admin/manager/service_advisor |
| Work tasks | `GET /work-tasks`, `GET /work-tasks/{id}` | semua staff |
| Work tasks | `POST /work-tasks/{id}/{start\|pause\|finish}` | + `mekanik` |
| Findings | `GET /findings`, `GET /findings/{id}` | semua staff |
| Inspections | `GET /services/{id}/inspections` → `{checklist, findings}` | semua staff |
| Photos | `POST /vehicles/{id}/images`, `DELETE /vehicles/{id}/images/{imageId}` (multipart `image: jpeg|png|webp ≤5MB`, `caption?`; response `data.url` absolut) | upload: +mekanik; hapus: admin/manager/service_advisor |
| Photos | `POST /services/{id}/images`, `DELETE /services/{id}/images/{imageId}` (field `type?` tambahan) | sama dengan kendaraan |
| Device tokens (push-ready) | `POST /device-tokens` `{token, platform: android\|ios\|web}` (idempoten), `DELETE /device-tokens` `{token}` | semua staff (hanya token milik sendiri) |
| Technicians | `GET /technicians` (role `mekanik`, aktif) | semua staff |
| Invoices | `GET` baca semua staff; tulis: lihat tabel | store/update: admin/manager; delete: admin |
| Payments | `POST /invoices/{id}/payments` `{..., idempotency_key?}` | admin/manager/kasir |
| Invoice PDF | `GET /invoices/{id}/pdf` | semua staff |
| Products | `GET` semua staff; tulis `inventory`; delete `admin`; `POST .../stock-adjust` manager/inventory |
| Purchases | `GET` semua staff; tulis manager; delete admin; `receive` +inventory |
| Purchase orders | `POST /purchase-orders/{id}/receive`, `POST .../{submit\|approve\|close}` | login (transisi via service) |
| Sales | baca: +kasir/inventory; tulis: +kasir; delete: admin |
| Suppliers | baca: +inventory; tulis: +inventory; delete: admin |
| Incomes/Expenses | penuh: admin/manager |
| POS | `POST /pos/open`, `/pos/close`, `/pos/checkout` (`idempotency_key?`) | admin/manager/kasir |
| Commissions | `GET /commissions`, `POST .../mark-paid` | mark-paid: admin/manager |
| Warranty | baca semua staff; tulis service_advisor; update manager; delete admin |
| Reports | `GET /reports/{service\|sales\|stock\|financial}` | semua staff (branch-scoped) |

Peran yang dikenal: `super_admin`, `admin`, `manager`, `kasir`, `mekanik`, `service_advisor`, `inventory`.
`super_admin`/`admin` = akses semua cabang. Selain itu **wajib** punya assignment cabang,
tanpa assignment = **DENIED (data nol)** — tidak pernah dianggap "semua cabang".

## 5. Branch context

- Web memakai session `current_branch_id`; API stateless memakai assignment user.
- User 1 cabang → otomatis cabang itu. User multi-cabang → filter mengikuti assignment
  (global scope), tidak perlu header khusus.
- Jangan kirim `branch_id` untuk mengakali: create/update dengan `branch_id` yang tidak
  diakses → `403`. ID milik cabang lain → `404` (binding + global scope).
- `GET /branches` = daftar cabang yang boleh diakses user ini (untuk branch picker).

## 6. State machine penting (jangan dilewati client)

Estimasi: `draft → sent → waiting_approval → approved | partially_approved | rejected`,
bisa `expired`, `superseded` (revisi), `converted` (→ invoice).
Persetujuan parsial: `POST .../decide` dengan `decisions: [{group_id, decision: approved|rejected}]`.
Hanya item/grup approved yang bisa jadi task (`createTasksForApprovedGroups`) dan invoice.

Task: `pending | ready → in_progress ⇄ paused → completed → qc_pending → qc_passed | qc_failed`.
`start` idempoten saat sudah `in_progress`; status ilegal → `422` (bukan transisi diam-diam).

QC: `submitQc(package, passed|failed)` — gagal wajib `notes`; finding terkait hanya
resolve lewat QC (atau checklist OK sebelum ada approved work).

Invoice → Payment: `paid_amount` selalu = Σ payment; invoice lunas (`payment_status=2`)
keluar dari AR aging. Edit invoice unpaid = realign jurnal (AR/COGS ditulis ulang).

## 7. Idempotency & offline sync

- `POST /invoices/{id}/payments` dan `POST /pos/checkout` menerima `idempotency_key`
  (string ≤64). Kirim ulang dengan key sama = dapat record yang sama, tidak dobel.
- Approval estimasi dan `startTask` idempoten untuk retry aman.
- Pola offline yang disarankan: outbox lokal + `idempotency_key = uuid` per transaksi
  + pull ulang (`GET` detail) setelah online untuk rekonsiliasi.
- Konflik: **server menang** untuk dokumen finansial; stok dilindungi lock baris
  (negative stock ditolak); jangan optimistis mengubah total/harga dari client —
  uang selalu dihitung ulang server-side.

## 8. Pembayaran & webhook (server-side, bukan dari Flutter)

- `PaymentGatewayService::createForInvoice` membuat `PaymentLink` (Midtrans configurable
  via `MIDTRANS_*`); callback diverifikasi signature + anti-replay di
  `handleCallback` (`payment/callback/*`, bebas CSRF, di luar auth).
- Flutter cukup: tampilkan link/instruksi dari `PaymentLink`, lalu poll status invoice.

## 9. Upload & gambar

- `POST /vehicles/{id}/images` dan `POST /services/{id}/images`: multipart
  (`image` wajib, `mimes:jpeg,png,webp`, maks 5MB; nama file acak server-side;
  response `data.url` absolut siap ditampilkan).
- Hapus via `DELETE .../images/{imageId}` — di-scope lewat parent, ID lintas cabang → 404.
  File fisik ikut terhapus (model hook, semua jalur: web/API/console).
- Upload kendaraan juga tersedia via web; validasi dan disk sama (`public`).

## 10. Notifikasi

- `GET /notifications` = notifikasi database milik user (paginate).
- Event domain (booking, estimasi, approval, QC, ready, invoice, payment, garansi)
  dikirim via `NotificationService` (DB/mail/SMS/WA provider-configurable,
  bukan hardcoded). Token FCM per device didaftarkan via `POST /device-tokens`
  (idempoten, per-user); pengiriman push memakai tabel `fleet_notification_tokens`.

## 11. Batas & keamanan

- Login `throttle:10,1`; API authed `throttle:120,1`.
- Security headers aktif di web+API (`SecurityHeaders` middleware).
- Tidak ada registrasi publik; user dibuat oleh admin.
- Jangan simpan token di storage tidak terenkripsi; hapus saat logout.

## 12. Contoh cepat

```dart
final login = await dio.post('/api/v1/login',
  data: {'email': e, 'password': p});
final token = login.data['token']; // simpan aman
dio.options.headers['Authorization'] = 'Bearer $token';

final est = await dio.get('/api/v1/estimates',
  queryParameters: {'status': 'waiting_approval', 'per_page': 20});
await dio.post('/api/v1/estimates/12/approve', data: {'method': 'manual'});
await dio.post('/api/v1/work-tasks/34/start');
await dio.post('/api/v1/invoices/56/payments', data: {
  'amount': 150000, 'payment_method_id': 1,
  'idempotency_key': const Uuid().v4(), // retry aman
});
```
