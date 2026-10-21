# WORKFLOW — Alur Bengkel Kanonis

```
BOOKING / WALK-IN → CHECK-IN → INSPEKSI (checklist) → FINDING
  → WORK PACKAGE → ESTIMASI → APPROVAL (parsial per grup)
  → WORK TASK (teknisi + timer) → QC → REWORK (jika gagal)
  → INVOICE (hanya scope approved) → PAYMENT → READY
  → GATE PASS → RELEASE → COMPLETED
```

Otoritas kode: `WorkshopFlowService`, `EstimateService`,
`WorkshopInvoiceGuard`, `GatePassEligibilityService`, `WorkshopProgressService`.

## Aturan keras

- Finding & work package tahap wajib; estimasi tidak melompatinya.
- Approval per grup work package (`submitGroupDecisions`); hanya grup approved
  yang menjadi task (`createTasksForApprovedGroups`) dan invoice.
- Estimasi tak menyentuh stok/jurnal/piutang. Status ilegal → 409/422.
- Ditolak/expired/superseded tak bisa aktif lagi tanpa revisi (`revise()` versi baru).
- Estimasi expired otomatis (`expireLapsed`); token publik DRAFT = 404.
- Task: start idempoten, pause/resume/finish guarded + time entries server-side.
- QC gagal wajib alasan; finding resolve hanya via QC (atau checklist OK
  sebelum ada approved work). Rework = package/task baru, bukan edit diam-diam.
- Invoice dari scope komersial approved saja; payment idempoten;
  gate pass hanya bila syarat lunas/selesai terpenuhi.

Test E2E: `EndToEndWorkshopFlowTest` (booking → release) +
`AdditionalWorkRequiresApprovalTest`, `EstimatePartialApprovalTest`,
`QcFailureDoesNotResolveFindingTest`, `EstimateExpiryTest`, `EstimateRejectTest`.
