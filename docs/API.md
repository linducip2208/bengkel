# API — Bengkel Paten (`/api/v1`, 110 routes)

Kontrak lengkap untuk Flutter: `docs/FLUTTER-API-CONTRACT.md`.
Dokumen ini ringkasan server-side.

## Konvensi

- Auth: Sanctum Bearer. Login `throttle:10,1`, grup auth `throttle:120,1`.
- Koleksi: paginator Laravel (`data`, `total`, `current_page`...), `?per_page=` (default 20).
- Detail: `{data: {...}}`. Aksi: `{message, data?}` + status benar
  (200/201/422/401/403/404; lintas cabang = 404).
- Validasi gagal: 422 `{message, errors}`. Aturan bisnis: 422 `{message}`.
- Tulis dibatasi role per route (lihat `routes/api.php`); baca terbuka untuk staff login
  kecuali finansial (admin/manager).

## Pemetaan domain → resource

| Domain perintah | Resource API |
|---|---|
| /auth | login/logout/me |
| /dashboard | dashboard/stats, master-data |
| /bookings, /services (= jobcards/work-orders) | bookings, services, jobcards |
| /inspections, /findings | services/{id}/inspections, findings |
| /work-packages, /approvals, /qc | work-packages, estimates approve/reject/decide, work-packages/{id}/qc |
| /estimates | estimates + convert |
| /work-tasks, /technicians | work-tasks {start,pause,resume,finish}, technicians |
| /inventory | products (+stock-adjust), reports/stock |
| /suppliers, /purchases | suppliers, purchases, purchase-orders/{receive,submit,approve,close} |
| /invoices, /payments | invoices (+pdf), invoices/{id}/payments (idempotency_key) |
| /pos | pos/open, pos/close, pos/checkout (idempotency_key) |
| /reports | reports/{service,sales,stock,financial} |
| /notifications, /branches | notifications, branches, device-tokens |

Catatan jujur: tidak ada resource `work-orders` terpisah — work order =
jobcard = service (`jobcards` + `services/complete`). Tidak ada endpoint palsu.

## Jaminan

- Server otoritatif: total/harga/stok/status dihitung & divalidasi server.
- `branch_id` dari client tidak dipercaya (diabaikan/ditolak, teruji).
- Semua baca ter-branch-scope otomatis; tulis kritis transaksional + idempoten
  (payments, POS checkout, approval, task start).
