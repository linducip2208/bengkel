<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Jobcard {{ $service->job_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h2 { margin: 0; }
        .header small { color: #666; }
        table.detail { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.detail td { padding: 4px 8px; vertical-align: top; }
        table.detail td:first-child { width: 180px; font-weight: bold; }
        .section-title { background: #f0f0f0; padding: 6px 10px; font-weight: bold; margin: 15px 0 8px 0; }
        table.checklist { width: 100%; border-collapse: collapse; }
        table.checklist th, table.checklist td { border: 1px solid #ccc; padding: 6px 8px; font-size: 12px; }
        table.checklist th { background: #eee; }
        .footer { margin-top: 30px; text-align: right; font-size: 12px; }
        .signature { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature div { text-align: center; width: 30%; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="text-align:left;">
                <h2>JOBCARD</h2>
                <small>Bengkel Paten</small>
                <h4>{{ $service->job_no }}</h4>
            </div>
            <div id="qrcode" style="width:120px;height:120px;"></div>
        </div>
    </div>

    <table class="detail">
        <tr>
            <td>Pelanggan</td>
            <td>: {{ $service->customer->name ?? '-' }}</td>
            <td>No. HP</td>
            <td>: {{ $service->customer->phone ?? '-' }}</td>
        </tr>
        <tr>
            <td>Kendaraan</td>
            <td>: {{ $service->vehicle->number_plate ?? '-' }} ({{ $service->vehicle->vehicleBrand->vehicle_brand ?? '' }} {{ $service->vehicle->model_name ?? '' }})</td>
            <td>Tipe</td>
            <td>: {{ $service->vehicle->vehicleType->vehicle_type ?? '-' }}</td>
        </tr>
        <tr>
            <td>Kategori</td>
            <td>: {{ $service->repairCategory->repair_category_name ?? '-' }}</td>
            <td>Tanggal</td>
            <td>: {{ $service->service_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td>Judul</td>
            <td colspan="3">: {{ $service->title }}</td>
        </tr>
        @if($service->jobcardDetail)
        <tr>
            <td>Odometer Masuk</td>
            <td>: {{ number_format($service->jobcardDetail->odometer_in, 0, ',', '.') }} km</td>
            <td>Tgl Masuk</td>
            <td>: {{ $service->jobcardDetail->in_date?->format('d M Y H:i') ?? '-' }}</td>
        </tr>
        @if($service->jobcardDetail->odometer_out)
        <tr>
            <td>Odometer Keluar</td>
            <td>: {{ number_format($service->jobcardDetail->odometer_out, 0, ',', '.') }} km</td>
            <td>Tgl Keluar</td>
            <td>: {{ $service->jobcardDetail->out_date?->format('d M Y H:i') ?? '-' }}</td>
        </tr>
        @endif
        @endif
    </table>

    <div class="section-title">Deskripsi Pekerjaan</div>
    <p>{{ $service->description ?: 'Tidak ada deskripsi.' }}</p>

    @if($service->technicians->count())
    <div class="section-title">Teknisi</div>
    <p>{{ $service->technicians->pluck('name')->join(', ') }}</p>
    @endif

    @if($service->serviceObservationPoints->count())
    <div class="section-title">Hasil Pemeriksaan</div>
    <table class="checklist">
        <thead>
            <tr><th>Poin Pemeriksaan</th><th style="width:60px;text-align:center;">Status</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @foreach($service->serviceObservationPoints as $r)
            <tr>
                <td>{{ $r->observationPoint->observation_point ?? '-' }}</td>
                <td style="text-align:center;">{{ $r->checked ? 'OK' : 'NG' }}</td>
                <td>{{ $r->comment ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>Dicetak: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="signature">
        <div>
            <p>Teknisi</p>
            <br><br><br>
            <p>(_______________)</p>
        </div>
        <div>
            <p>Service Advisor</p>
            <br><br><br>
            <p>(_______________)</p>
        </div>
        <div>
            <p>Pelanggan</p>
            <br><br><br>
            <p>(_______________)</p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>new QRCode(document.getElementById('qrcode'), {text:'{{ route('services.show', $service) }}',width:120,height:120});</script>
</body>
</html>
