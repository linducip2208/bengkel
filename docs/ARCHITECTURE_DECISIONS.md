# Architecture Decisions (ADRs — ringkas)

Keputusan arsitektur yang mengikat untuk Bengkel Paten. Detail alur:
`README.md` (canonical workshop flow), kontrak API: `docs/flutter-api-contract.md`.

## ADR-1: Laravel single source of truth
Seluruh business logic di service/domain layer (`app/Services`).
API (`app/Http/Controllers/Api`) dan web adalah lapisan tipis —
tidak ada duplikasi logic per client (termasuk Flutter).

## ADR-2: Branch isolation fail-closed
`BranchScope` (global scope) + `HasBranchScope` (create/update guard) +
`BranchAccess` (value object: `unrestricted | restricted(ids) | denied`).
Restricted tanpa assignment = `denied` (nol baris), tidak pernah fallback ke NULL.
Route-model binding lintas cabang = 404 (bukan 403) agar anti-enumerasi.
Regression: `BranchIsolationTest`, `BranchIsolationFailClosedTest`, `ApiWorkshopContractTest`.

## ADR-3: Workshop sebagai state machine eksplisit
`WorkshopFlowService` + `EstimateService` + guard (`WorkshopInvoiceGuard`,
`GatePassEligibilityService`). Transisi ilegal → 409/422, bukan silent skip.
Keuangan tidak pernah dibuat oleh estimasi (no journal, no stock, no AR).

## ADR-4: Uang & stok transaksional
- Uang dihitung ulang server-side (`computeTotals`), presisi DECIMAL.
- Jurnal selalu balanced (`AutoJournalService`), edit invoice = realign.
- Stok via satu choke-point (`StockService`, row-lock, ledger `StockRecord`/`StockHistory`);
  negative stock ditolak; race diuji (`InventoryConcurrencyTest`).
- Payment & POS checkout idempoten via `idempotency_key`.

## ADR-5: API untuk Flutter, bukan duplikat Flutter
Endpoint workshop (`ApiWorkshopController`) memakai ulang service yang sama
dengan web. Response paginator Laravel apa adanya; error konsisten
(data/meta/message). Tidak ada endpoint "khusus Flutter" yang menduplikasi logic.

## ADR-6: Lisensi komersial
`composer.json` = `proprietary`, konsisten dengan `README.md`.
Pairing `whitelabel.co.id` (License v3) via `RequirePair` middleware;
API stateless tidak dipasangi `RequirePair` (hanya web).
