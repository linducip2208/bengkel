<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: -apple-system, Segoe UI, Roboto, sans-serif; line-height: 1.5; color: #1f2937; max-width: 640px; margin: 0 auto; padding: 1.5rem;">
    <h2 style="color: #2563eb; margin-bottom: 0.25rem;">{{ $appName }}</h2>
    <p style="color: #6b7280; margin-top: 0;">Invoice {{ $invoice->invoice_number }}</p>

    <p>Halo {{ $invoice->customer->name ?? 'Pelanggan' }},</p>
    <p>Terima kasih sudah mempercayakan kendaraan Anda ke kami. Berikut detail invoice Anda:</p>

    <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
        <tr>
            <td style="padding: 6px 10px; background: #f3f4f6; border: 1px solid #e5e7eb;"><b>Nomor Invoice</b></td>
            <td style="padding: 6px 10px; border: 1px solid #e5e7eb;">{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; background: #f3f4f6; border: 1px solid #e5e7eb;"><b>Tanggal</b></td>
            <td style="padding: 6px 10px; border: 1px solid #e5e7eb;">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; background: #f3f4f6; border: 1px solid #e5e7eb;"><b>Total</b></td>
            <td style="padding: 6px 10px; border: 1px solid #e5e7eb;">@money($invoice->grand_total)</td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; background: #f3f4f6; border: 1px solid #e5e7eb;"><b>Sudah Dibayar</b></td>
            <td style="padding: 6px 10px; border: 1px solid #e5e7eb;">@money($totalPaid)</td>
        </tr>
        <tr>
            <td style="padding: 6px 10px; background: #f3f4f6; border: 1px solid #e5e7eb;"><b>Sisa</b></td>
            <td style="padding: 6px 10px; border: 1px solid #e5e7eb; color: {{ $remaining > 0 ? '#dc2626' : '#16a34a' }};">
                <b>@money($remaining)</b>
            </td>
        </tr>
    </table>

    <p>Detail lengkap invoice ada di file PDF terlampir.</p>

    @if($remaining > 0)
    <p style="background: #fef3c7; padding: 12px 16px; border-left: 4px solid #f59e0b; border-radius: 4px;">
        <b>Sisa pembayaran:</b> @money($remaining). Mohon pelunasan secepatnya.
    </p>
    @endif

    <p style="margin-top: 2rem; color: #6b7280; font-size: 0.9rem;">
        Email ini dikirim otomatis oleh sistem {{ $appName }}.<br>
        Untuk pertanyaan terkait invoice ini, balas langsung ke email ini.
    </p>
</body>
</html>
