<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checklist Pemeriksaan {{ $service->job_no }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 0; }

        .print-actions { text-align: right; margin-bottom: 12px; }
        .print-actions button, .print-actions a {
            display: inline-block; padding: 6px 14px; margin-left: 6px;
            border: 1px solid #444; border-radius: 4px; background: #fff;
            font-size: 13px; color: #111; text-decoration: none; cursor: pointer;
        }

        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 8px; }
        .doc-header .company { display: flex; gap: 10px; align-items: center; }
        .doc-header img { max-width: 56px; max-height: 56px; object-fit: contain; }
        .doc-header h2 { margin: 0; font-size: 17px; }
        .doc-header small { display: block; color: #444; font-size: 10.5px; }
        .doc-header .doc-title { text-align: right; }
        .doc-header .doc-title h1 { margin: 0; font-size: 15px; letter-spacing: 1px; }

        table.meta { width: 100%; border-collapse: collapse; margin: 10px 0 4px 0; }
        table.meta td { padding: 2px 4px; vertical-align: top; }
        table.meta .lbl { color: #444; width: 14%; white-space: nowrap; }
        table.meta .sep { width: 4%; }

        .obs-group { margin-top: 12px; page-break-inside: avoid; }
        .obs-group h3 {
            margin: 0 0 4px 0; font-size: 12px; text-transform: uppercase;
            letter-spacing: 0.5px; background: #efefef; padding: 4px 8px; border: 1px solid #999;
        }
        table.points { width: 100%; border-collapse: collapse; }
        table.points th, table.points td { border: 1px solid #aaa; padding: 4px 6px; vertical-align: top; }
        table.points th { background: #f7f7f7; font-size: 11px; text-transform: uppercase; }
        table.points td.no { width: 6%; text-align: center; }
        table.points td.status { width: 16%; text-align: center; font-weight: bold; }
        table.points td.note { width: 32%; }
        tr { page-break-inside: avoid; }

        .inspection-note { margin-top: 14px; page-break-inside: avoid; }
        .inspection-note .lbl { font-weight: bold; margin-bottom: 2px; }
        .inspection-note .line { border-bottom: 1px solid #111; height: 46px; }

        .signatures { display: flex; justify-content: space-between; gap: 18px; margin-top: 22px; page-break-inside: avoid; }
        .signatures .sig { width: 31%; text-align: center; }
        .signatures .sig .role { font-weight: bold; margin-bottom: 22px; }
        .signatures .sig .name { border-bottom: 1px solid #111; height: 30px; margin-bottom: 3px; }
        .signatures .sig .cap { font-size: 10px; color: #444; }

        .doc-footer { display: flex; justify-content: space-between; margin-top: 16px; font-size: 10px; color: #555; }

        @media print {
            .print-actions { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()"><i>&#128424;</i> Print</button>
        <a href="{{ route('observations.checklist', $service) }}">Kembali</a>
    </div>

    {{-- ============================ HEADER ============================ --}}
    <div class="doc-header">
        <div class="company">
            @if(! empty($company['logo']))
            <img src="{{ str_starts_with($company['logo'], 'http') ? $company['logo'] : asset('storage/'.$company['logo']) }}" alt="logo">
            @endif
            <div>
                <h2>{{ $company['name'] ?? config('app.name') }}</h2>
                <small>{{ $company['address'] ?? '' }}</small>
                <small>Telp: {{ $company['phone'] ?? '-' }}@if(! empty($company['email'])) &middot; Email: {{ $company['email'] }}@endif</small>
            </div>
        </div>
        <div class="doc-title">
            <h1>CHECKLIST PEMERIKSAAN KENDARAAN</h1>
        </div>
    </div>

    {{-- ============================ META ============================ --}}
    <table class="meta">
        <tr>
            <td class="lbl">No. Service</td><td>: <strong>{{ $service->job_no }}</strong></td>
            <td class="sep"></td>
            <td class="lbl">Tanggal</td><td>: {{ $service->service_date?->format('d/m/Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Customer</td><td>: {{ $service->customer?->name ?? '-' }}</td>
            <td class="sep"></td>
            <td class="lbl">Telepon</td><td>: {{ $service->customer?->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kendaraan</td>
            <td>: {{ trim(($service->vehicle?->vehicleBrand?->vehicle_brand ?? '').' '.($service->vehicle?->model_name ?? '')) ?: ($service->vehicle?->vehicleType?->vehicle_type ?? '-') }}</td>
            <td class="sep"></td>
            <td class="lbl">Plat</td><td>: {{ $service->vehicle?->number_plate ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">KM</td>
            <td>: {{ $service->jobcardDetail?->odometer_in !== null ? number_format((float) $service->jobcardDetail->odometer_in, 0, ',', '.') : ($service->vehicle?->odometer !== null ? number_format((float) $service->vehicle->odometer, 0, ',', '.') : '-') }}</td>
            <td class="sep"></td>
            <td class="lbl">Service Advisor</td><td>: {{ $service->serviceAdvisor?->name ?? '-' }}</td>
        </tr>
        @if($service->repairCategory)
        <tr>
            <td class="lbl">Kategori</td><td colspan="4">: {{ $service->repairCategory->repair_category_name }}</td>
        </tr>
        @endif
    </table>

    {{-- ============================ OBSERVATION GROUPS ============================ --}}
    @forelse($groupedPoints as $type => $points)
    <div class="obs-group">
        <h3>{{ $type }}</h3>
        <table class="points">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th>Poin Pemeriksaan</th>
                    <th class="status">Status</th>
                    <th class="note">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($points as $point)
                @php $result = $checkResults->get($point->id); @endphp
                <tr>
                    <td class="no">{{ $loop->iteration }}</td>
                    <td>{{ $point->observation_point }}</td>
                    <td class="status">{{ ($result && $result->checked) ? 'OK' : 'Belum Dicek' }}</td>
                    <td class="note">{{ $result?->comment ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
    <p class="text-center" style="margin-top:24px">Belum ada poin pemeriksaan.</p>
    @endforelse

    {{-- ============================ NOTES + SIGNATURES ============================ --}}
    <div class="inspection-note">
        <div class="lbl">Catatan Pemeriksaan:</div>
        <div class="line"></div>
    </div>

    <div class="signatures">
        <div class="sig">
            <div class="role">Teknisi / Pemeriksa</div>
            <div class="name"></div>
            <div class="cap">Nama &amp; Tanda tangan</div>
        </div>
        <div class="sig">
            <div class="role">Service Advisor</div>
            <div class="name"></div>
            <div class="cap">Nama &amp; Tanda tangan</div>
        </div>
        <div class="sig">
            <div class="role">Customer</div>
            <div class="name"></div>
            <div class="cap">Nama &amp; Tanda tangan</div>
        </div>
    </div>

    <div class="doc-footer">
        <span>Dicetak: {{ now()->format('d/m/Y H:i') }}</span>
        <span>No. Service: {{ $service->job_no }}</span>
    </div>
</body>
</html>
