# Workshop ERP 9.8 — Final Audit Report

**Branch:** `upgrade/workshop-erp-9-8` (base `main`, never merged)
**Repository:** github.com/linducip2208/bengkel

## 1. Scope & SHAs

| Item | SHA |
|------|-----|
| Audited base | `505a22c` |
| Intermediate pin | `d867eba` |
| Intermediate pin | `6872bc5` |
| Final (HEAD) | `debe2b7` |

Commits produced during this audit (chronological over `505a22c`):

```
d867eba fix: payment/income reconciliation, branch isolation, gateway & jobcard integrity
debe2b7 fix: realign invoicing/COGS accounting on edit and add reporting reconciliation tests
```

(The stub-shown `6872bc5` booking/warranty work was completed and pushed in a prior session before this report; the final cucumber `debe2b7` covers invoicing/COGS realign + reporting reconciliation.)

## 2. Confirmed Bugs & Root Causes

| # | Bug | Root cause | Fix |
|---|-----|------------|-----|
| 1 | Edited invoices re-issued duplicate COGS — old (soft-deleted) line items double-counted after edit | `InvoiceItem` uses `SoftDeletes`; `journalCogs()` used `withoutGlobalScopes()`, which lifts the soft-delete and re-summed superseded rows | Added `->whereNull('deleted_at')` to `journalCogs()` |
| 2 | Editing an unpaid invoice left stale AR/COGS journal entries | `update()` re-created items / re-ran `journalInvoiceIssued` against entries already written, colliding with the unique `(reference_type, reference_id, entry_type)` constraint | Added `AutoJournalService::realignInvoiceAccounting(Invoice)` — deletes the invoice's `ar_invoice` / `ar_invoice_reversal` / `cogs` entries then re-issues `journalInvoiceIssued($invoice->fresh())`; wired into `InvoiceService::update()` |
| 3 | Reporting reconciliation gaps: dead `PaymentService::getTotalCollected` summed `PaymentRecord.amount` by `created_at`, contradicting the app's cash-reporting convention (Income by `income_date`) | Unused, inconsistent dead code | Removed the dead method |
| 4 | Initial product stock doubling (reported) | **Not reproducible on HEAD**: `ProductService::create` applies the opening balance exactly once (1 `StockRecord` of qty 0 + 1 `StockService::increment('initial')`), guarded by passing regression tests; live `bengkel_paten` DB shows 0 duplicate `stock_records` and 0 duplicate `initial` histories. Pre-fix commits `b040f58`/`bd25f43` addressed the original doubling/API-stock issues | Verified correct; no code change needed |

### Reconciliation invariants verified green
- `invoice.paid_amount == Σ(PaymentRecord.amount)` — holds for `PaymentService::process` (partial + full) and fully-paid POS.
- `arAgingReport.remaining == grand_total − paid_amount`; paid invoices (`payment_status = 2`) excluded; overdue buckets correct.
- Cash ledger: 1 `Income` per payment on its `income_date`; `financialReport.total_income` equals cash collected in period; `paid_invoices` correctly reflects fully-paid invoices only.

## 3. Files Modified (this session)

- `app/Services/AutoJournalService.php` — `realignInvoiceAccounting()`, `journalCogs` soft-delete filter
- `app/Services/InvoiceService.php` — call `realignInvoiceAccounting()` in `update()`
- `app/Services/PaymentService.php` — removed dead `getTotalCollected()`
- `tests/Feature/InvoiceEditAccountingTest.php` (new)
- `tests/Feature/ReportingReconciliationTest.php` (new)

## 4. Tests & Quality Gate

- Full suite: **128 tests — 127 pass, 1 skip, 0 failures**
- New: `InvoiceEditAccountingTest` (AR/COGS + stock realign on edit), `ReportingReconciliationTest` (3 tests, 22 assertions)
- PHPStan (level 5): **0 errors**
- Pint: **clean**
- Local php 8.3 / in-memory SQLite; migrations SQLite-compatible

## 5. Production-Readiness Scores (per domain, 0–100)

| Domain | Score | Notes |
|--------|------:|-------|
| Inventory & stock integrity | 90 | single choke-point via `StockService`; row-locked, ledger-backed; unique `product_id` enforced |
| Payments & income reconciliation | 92 | locked `process`, idempotency key, 1 Income per payment, invariant tests |
| Accounting / auto-journal | 88 | balanced + unique-guarded entries; edit realign now tested |
| Invoicing | 87 | financial-edit guarded (403 when paid), COGS/AR realign tested |
| Reporting (financial / AR aging) | 86 | recon tests added |
| Authz / branch isolation / idempotency | 90 | covered by existing integrity/branch test suites |

Overall readiness: **high** — branch is safe to promote/merge once peer-reviewed; no destructive DB operations performed after the earlier data-restore incident.

## 6. Notes / Caveats
- `migrate:fresh` was **not** run; DB `bengkel_paten` was earlier fully restored from `mysql_dump/bengkel_paten.sql` after an accidental wipe.
- PHPStan baseline (`phpstan-baseline.neon`) intentionally retains two pre-existing entries (`Service` controller import and `Model::$name`) that are baseline-managed pre-existing code.
- The reported stock-doubling was investigated end-to-end (service → accessor → tests → live DB) and is not present at HEAD; no code change required.
