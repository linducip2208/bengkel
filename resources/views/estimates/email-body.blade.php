<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Estimasi {{ $estimate->estimate_number }} — {{ config('app.name') }}</title>
</head>
<body style="font-family: Arial, sans-serif; color:#1a1a1a;">
    <p>Halo {{ $customer['name'] ?? 'Pelanggan' }},</p>

    <p>Berikut kami lampirkan estimasi servis untuk kendaraan Anda
    ({{ $estimate->snapshotVehicle()['number_plate'] ?? '-' }}).</p>

    <ul>
        <li>No. Estimasi: <strong>{{ $estimate->estimate_number }}</strong> (v{{ $estimate->version }})</li>
        <li>No. Service: {{ $estimate->snapshotService()['number'] ?? '-' }}</li>
        <li>Total Estimasi: <strong>Rp {{ number_format((float) $estimate->grand_total, 0, ',', '.') }}</strong></li>
        <li>Berlaku sampai: {{ $estimate->valid_until?->format('d M Y') ?? '-' }}</li>
    </ul>

    <p>Anda dapat menyetujui estimasi melalui tautan berikut:<br>
    <a href="{{ route('public.estimate.show', $estimate->public_token ?? '') }}">{{ route('public.estimate.show', $estimate->public_token ?? '') }}</a></p>

    <p>Terima kasih.<br>{{ $appName }}</p>

    <p style="color:#888;font-size:11px">Dokumen ini adalah estimasi — bukan invoice dan bukan bukti pembayaran.</p>
</body>
</html>
