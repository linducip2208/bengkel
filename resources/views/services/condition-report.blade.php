<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kondisi Kendaraan — {{ $service->job_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #e5e7eb;
            color: #111827;
            font-size: 12px;
            padding: 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 12mm;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,.12);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .brand { display: flex; align-items: center; gap: 10px; }
        .brand-logo {
            width: 46px; height: 46px; border-radius: 8px;
            background: #111827; color: #ffffff;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 800;
        }
        .brand-name { font-size: 20px; font-weight: 800; letter-spacing: .5px; }
        .brand-sub { font-size: 11px; color: #6b7280; margin-top: 2px; text-transform: uppercase; letter-spacing: 1px; }
        .report-title { text-align: right; }
        .report-title .t1 { font-size: 16px; font-weight: 800; }
        .report-title .t2 { font-size: 11px; color: #6b7280; margin-top: 2px; }
        .report-title .job { display: inline-block; margin-top: 6px; background: #111827; color: #fff; padding: 4px 10px; border-radius: 4px; font-weight: 700; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .info-cell { padding: 8px 12px; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .info-cell:nth-child(3n) { border-right: none; }
        .info-cell:nth-last-child(-n+3) { border-bottom: none; }
        .info-cell .label { font-size: 9px; text-transform: uppercase; color: #6b7280; letter-spacing: .5px; margin-bottom: 2px; }
        .info-cell .value { font-size: 13px; font-weight: 700; }

        .section-title {
            font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px;
            background: #111827; color: #ffffff; padding: 6px 12px; border-radius: 3px;
            margin: 18px 0 8px;
        }
        table.checks { width: 100%; border-collapse: collapse; }
        table.checks th {
            text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .5px;
            color: #6b7280; padding: 6px 10px; border-bottom: 2px solid #e5e7eb;
        }
        table.checks td { padding: 6px 10px; border-bottom: 1px solid #f3f4f6; font-size: 12px; vertical-align: top; }
        table.checks tr:nth-child(even) td { background: #f9fafb; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; }
        .badge.ok { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge.ng { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .badge.na { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
        .comment { color: #6b7280; font-size: 11px; }

        .photo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .photo-col h4 { font-size: 12px; font-weight: 800; margin-bottom: 6px; color: #374151; }
        .photo-col h4.before { color: #b91c1c; }
        .photo-col h4.after { color: #15803d; }
        .photo-items { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
        .photo-item { border: 1px solid #e5e7eb; border-radius: 4px; overflow: hidden; }
        .photo-item img { width: 100%; height: 90px; object-fit: cover; display: block; }
        .photo-item .cap { font-size: 9px; color: #6b7280; padding: 3px 6px; background: #f9fafb; }
        .muted { color: #9ca3af; font-size: 11px; font-style: italic; }

        .signatures { display: flex; justify-content: space-between; gap: 24px; margin-top: 40px; page-break-inside: avoid; }
        .sign-block { flex: 1; text-align: center; }
        .sign-block .name { font-weight: 800; font-size: 13px; margin-top: 60px; border-top: 1px solid #111827; padding-top: 6px; }
        .sign-block .role { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
        .sign-block .date-line { font-size: 10px; color: #9ca3af; margin-top: 4px; }

        .footer-note { margin-top: 24px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #f3f4f6; padding-top: 10px; }

        .print-btn {
            display: block; margin: 16px auto 0; padding: 10px 24px;
            background: #2563eb; color: #fff; border: none; border-radius: 6px;
            font-size: 14px; cursor: pointer;
        }

        @media print {
            body { background: none; padding: 0; }
            .page { box-shadow: none; max-width: none; padding: 0; border-radius: 0; }
            .no-print { display: none; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="brand">
                <div class="brand-logo">{{ strtoupper(mb_substr($companyName, 0, 2)) }}</div>
                <div>
                    <div class="brand-name">{{ $companyName }}</div>
                    <div class="brand-sub">Bengkel &amp; Servis Kendaraan</div>
                </div>
            </div>
            <div class="report-title">
                <div class="t1">Laporan Kondisi Kendaraan</div>
                <div class="t2">Vehicle Condition Report</div>
                <div class="job">{{ $service->job_no }}</div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-cell">
                <div class="label">Pelanggan / Customer</div>
                <div class="value">{{ $service->customer?->name ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">No. HP</div>
                <div class="value">{{ $service->customer?->phone ?? $service->customer?->mobile ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Tanggal / Date</div>
                <div class="value">{{ $service->service_date?->format('d M Y H:i') ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">No. Polisi / Plate</div>
                <div class="value">{{ $service->vehicle?->number_plate ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Kendaraan / Vehicle</div>
                <div class="value">{{ $service->vehicle?->vehicleBrand?->vehicle_brand ?? '' }} {{ $service->vehicle?->model_name ?? '' }} {{ $service->vehicle?->model_year ?? '' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Kategori / Category</div>
                <div class="value">{{ $service->repairCategory?->repair_category_name ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Odometer Masuk</div>
                <div class="value">{{ $service->jobcardDetail?->odometer_in ? number_format((int) $service->jobcardDetail->odometer_in, 0, ',', '.') . ' km' : '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Odometer Keluar</div>
                <div class="value">{{ $service->jobcardDetail?->odometer_out ? number_format((int) $service->jobcardDetail->odometer_out, 0, ',', '.') . ' km' : '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="label">Service Advisor</div>
                <div class="value">{{ $service->serviceAdvisor?->name ?? '-' }}</div>
            </div>
        </div>

        @php
            $grouped = $service->serviceObservationPoints->groupBy(
                fn($r) => $r->observationPoint?->observationType?->observation_type ?? 'Lainnya'
            );
            $beforeImages = $service->images->where('type', 'before');
            $afterImages = $service->images->where('type', 'after');
        @endphp

        @if($grouped->isNotEmpty())
            <div class="section-title">Hasil Pemeriksaan Kondisi</div>
            @foreach($grouped as $type => $points)
                <table class="checks">
                    <thead>
                        <tr>
                            <th style="width:40%;">{{ $type }}</th>
                            <th style="width:20%;">Status</th>
                            <th>Catatan / Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($points as $p)
                        <tr>
                            <td>{{ $p->observationPoint?->observation_point ?? '-' }}</td>
                            <td>
                                @if($p->checked)
                                    <span class="badge ok">OK</span>
                                @else
                                    <span class="badge ng">NG</span>
                                @endif
                            </td>
                            <td class="comment">{{ $p->comment ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @else
            <div class="section-title">Hasil Pemeriksaan Kondisi</div>
            <p class="muted">Belum ada data pemeriksaan kondisi.</p>
        @endif

        @if($beforeImages->isNotEmpty() || $afterImages->isNotEmpty())
            <div class="section-title">Dokumentasi Foto</div>
            <div class="photo-grid">
                <div class="photo-col">
                    <h4 class="before">SEBELUM (Before)</h4>
                    @if($beforeImages->isNotEmpty())
                        <div class="photo-items">
                            @foreach($beforeImages as $img)
                            <div class="photo-item">
                                <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $img->caption }}">
                                @if($img->caption)<div class="cap">{{ $img->caption }}</div>@endif
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="muted">Tidak ada foto.</p>
                    @endif
                </div>
                <div class="photo-col">
                    <h4 class="after">SESUDAH (After)</h4>
                    @if($afterImages->isNotEmpty())
                        <div class="photo-items">
                            @foreach($afterImages as $img)
                            <div class="photo-item">
                                <img src="{{ asset('storage/' . $img->image_path) }}" alt="{{ $img->caption }}">
                                @if($img->caption)<div class="cap">{{ $img->caption }}</div>@endif
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="muted">Tidak ada foto.</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="signatures">
            <div class="sign-block">
                <div class="role">Teknisi / Technician</div>
                <div class="name">{{ $service->technicians->isNotEmpty() ? $service->technicians->pluck('name')->implode(', ') : '..........................' }}</div>
                <div class="date-line">Tanda tangan &amp; tanggal</div>
            </div>
            <div class="sign-block">
                <div class="role">Service Advisor</div>
                <div class="name">{{ $service->serviceAdvisor?->name ?? '..........................' }}</div>
                <div class="date-line">Tanda tangan &amp; tanggal</div>
            </div>
            <div class="sign-block">
                <div class="role">Pelanggan / Customer</div>
                <div class="name">{{ $service->customer?->name ?? '..........................' }}</div>
                <div class="date-line">Tanda tangan &amp; tanggal</div>
            </div>
        </div>

        <div class="footer-note">
            Dokumen ini adalah laporan kondisi kendaraan pada saat servis. — {{ $companyName }}
        </div>
    </div>

    <button class="print-btn no-print" onclick="window.print()">Cetak Laporan</button>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
