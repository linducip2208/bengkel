# ARCHITECTURE — Bengkel Paten

Laravel 13 (PHP 8.3) + MySQL + Tabler (Vite, aset lokal) + Sanctum API.
Keputusan mengikat: `docs/ARCHITECTURE_DECISIONS.md` (ADR-1..6).

## Lapisan

```
routes/web.php + routes/api.php
  -> Controllers (tipis: validasi + otorisasi + delegasi)
    -> Services (domain logic, transaksi, state machine) — app/Services
      -> Models (relasi, casts, fillable, global scope) — app/Models
        -> MySQL (145 migrasi)
```

Response API: `app/Http/Resources`. Otorisasi: Spatie roles/permissions +
policies/gates + `BranchScope`/`HasBranchScope`/`BranchAccess`.
Error JSON terpusat: `bootstrap/app.php` (404 lintas cabang tetap 404).

## Domain inti

- Workshop: `BookingService`, `ObservationService`, `WorkshopFlowService`
  (checklist → finding → work package → estimate → approval parsial →
  task → QC → invoice → payment → gate pass/release),
  `EstimateService`, `ServiceService`, `WorkshopInvoiceGuard`,
  `GatePassEligibilityService`, `WorkshopProgressService`.
- Inventory: `StockService` (satu choke-point, row-lock, ledger),
  `ProductService`, `PurchaseService`, `PurchaseOrderWorkflowService`,
  `PurchaseOrderReceiptService`.
- Keuangan: `InvoiceService`, `PaymentService` (idempoten),
  `PosService`/`SaleService`, `IncomeService`, `ExpenseService`,
  `AutoJournalService` (jurnal balanced + realign).
- Notifikasi: `NotificationService` (DB/mail/SMS/WA, provider configurable).
- Lisensi komersial: `LicenseClient` + `RequirePair` (web only).

## Cross-cutting

- Multi-branch fail-closed (§ SECURITY.md).
- Uang dihitung ulang server-side; stok anti-negatif; operasi kritis idempoten.
- Queue (`database` default, Redis siap via env) untuk notifikasi/laporan berat.
- Health: `/up` dan `/api/v1/health` (db + cache + queue).
