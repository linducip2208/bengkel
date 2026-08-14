<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stiker Servis Berikutnya — {{ $service->job_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; background: #e5e7eb; display: flex; justify-content: center; padding: 20px; }
        .sticker {
            width: 90mm;
            height: 60mm;
            border: 2px dashed #9ca3af;
            background: #ffffff;
            padding: 6mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            font-size: 11px;
            color: #111827;
        }
        .sticker-header { text-align: center; border-bottom: 1px solid #d1d5db; padding-bottom: 2mm; }
        .sticker-header .brand { font-size: 15px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; }
        .sticker-header .sub { font-size: 8px; color: #6b7280; margin-top: 0.5mm; }
        .plate {
            text-align: center;
            background: #1e293b;
            color: #ffffff;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 2px;
            padding: 1.5mm;
            border-radius: 3px;
            margin: 2mm 0;
        }
        .sticker-body { flex: 1; }
        .row { display: flex; justify-content: space-between; padding: 1mm 0; }
        .row .label { color: #6b7280; font-size: 9px; text-transform: uppercase; }
        .row .value { font-weight: 700; text-align: right; }
        .next-date { font-size: 15px; font-weight: 800; color: #dc2626; }
        .sticker-footer { text-align: center; font-size: 8px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 1.5mm; }

        @media print {
            body { background: none; padding: 0; }
            .sticker { border: 1px solid #000; }
            .no-print { display: none; }
        }

        .print-btn {
            display: block;
            margin: 15px auto 0;
            padding: 10px 24px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div>
        <div class="sticker">
            <div class="sticker-header">
                <div class="brand">{{ $companyName }}</div>
                <div class="sub">SERVIS BERIKUTNYA / NEXT SERVICE</div>
            </div>

            <div class="plate">{{ $service->vehicle?->number_plate ?? '-' }}</div>

            <div class="sticker-body">
                <div class="row">
                    <span class="label">Pelanggan</span>
                    <span class="value">{{ $service->customer?->name ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">Kendaraan</span>
                    <span class="value">{{ $service->vehicle?->vehicleBrand?->vehicle_brand ?? '' }} {{ $service->vehicle?->model_name ?? '' }}</span>
                </div>
                <div class="row">
                    <span class="label">Kategori</span>
                    <span class="value">{{ $service->repairCategory?->repair_category_name ?? '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">Tanggal Servis Berikutnya</span>
                    <span class="value next-date">{{ $nextServiceDate ? \Carbon\Carbon::parse($nextServiceDate)->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="row">
                    <span class="label">KM Servis Berikutnya</span>
                    <span class="value">{{ $nextServiceKm ? number_format((float) $nextServiceKm, 0, ',', '.') . ' km' : '-' }}</span>
                </div>
            </div>

            <div class="sticker-footer">Terima kasih — jaga kendaraan Anda tetap prima.</div>
        </div>

        <button class="print-btn no-print" onclick="window.print()">Cetak Stiker</button>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
</body>
</html>
