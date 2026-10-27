# PERFORMANCE REVIEW V11 — hanya yang diukur/diubah

## Perubahan (semua output-identik, dikunci test)
- financialReport: 3N → 3 query total (GROUP BY per bulan diganti agregat rentang + group PHP).
- branchComparison: 4N → 2 query GROUP BY tunggal.
- serviceReport: N User::find → 1 whereIn batch.
- partsUsageReport: N Product::find → 1 whereIn + with(productType).
- services.index: +6 relasi eager (loadMissing yang sama jadi no-op).
- CustomerService::list: +withCount vehicles/services (ganti N+1 query per baris).
- Index DB baru (migrasi additive): invoices(payment_status, invoice_date, invoice_type), services(done_status).
- Catatan: angka query di atas dari inspeksi kode, BUKAN dari pengukuran query-log/time — klaim "lebih cepat" tanpa benchmark dilarang; yang diklaim hanya pengurangan jumlah query dan output identik (test hijau).

## Belum diubah (sengaja)
- technicianTimeReport/workPackageTimeReport/HrmController N+1 kecil (beban pagination, risiko regresi > manfaat tanpa benchmark).
- arAging full-scan (bounded data длинные? tidak — dibiarkan, pagination akan ubah kontrak).
- stockReport agregat PHP (bounded katalog).
- Cash: tanpa cache baru (hindari stale data transaksi).

## Cara verifikasi ulang
- `DB::enableQueryLog()` di test ReportsAggregationTest bila ingin angka pasti (belum dilakukan — dicatat sebagai pekerjaan lanjutan ringan).
- Benchmark butuh dataset representatif + MySQL prod-like (tidak tersedia di env ini).
