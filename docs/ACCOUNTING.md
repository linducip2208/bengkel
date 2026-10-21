# ACCOUNTING — Bengkel Paten

Otoritas: `AutoJournalService`, `InvoiceService`, `PaymentService`,
`IncomeService`, `ExpenseService`. Semua angka otoritatif server-side (DECIMAL).

## Prinsip

- Jurnal selalu balanced (debit = kredit), dijaga unique
  `(reference_type, reference_id, entry_type`).
- Estimasi tak pernah menjurnal. Jurnal terbit saat: invoice diterbitkan (AR),
  pembayaran (kas), pembelian/penerimaan (AP/persediaan), COGS, retur,
  adjustment stok, expense.
- Edit invoice unpaid = `realignInvoiceAccounting` (hapus AR/COGS lama, terbitkan ulang).
- Periode: penutupan & reversal via kebijakan void/reversal (lihat menu finance);
  audit trail `ActivityLog` + histori income/expense.

## Laporan (semua dari data jurnal, bukan kosmetik)

Trial Balance, General Ledger, Profit & Loss, Balance Sheet, Cash Flow,
AR Aging, AP Aging, Tax Report, Journal Report, Account Ledger, Period Closing —
via `ReportService` + menu laporan (branch + date filter, export, pagination).

Rekonsiliasi teruji: `paid_amount` = Σ payment; AR aging = grand − paid;
lunas keluar dari aging; 1 Income per payment pada `income_date`-nya
(`InvoiceEditAccountingTest`, `ReportingReconciliationTest`, `JournalBalanceTest`).
