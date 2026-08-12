@extends('layouts.app')
@section('title', 'Struk POS')
@push('styles')
<style>
    .receipt {
        max-width: 320px;
        margin: 0 auto;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        background: #fff;
        padding: 1rem;
        border: 1px solid #d1d5db;
    }
    .receipt h5 { text-align: center; margin: 0 0 0.25rem; }
    .receipt .sub { text-align: center; font-size: 11px; color: #555; }
    .receipt hr { border: 0; border-top: 1px dashed #555; margin: 0.5rem 0; }
    .receipt table { width: 100%; }
    .receipt .right { text-align: right; }
    .receipt .total { font-weight: 700; font-size: 14px; }
    .receipt .change { color: #059669; font-weight: 700; }
    @media print {
        body * { visibility: hidden; }
        .receipt, .receipt * { visibility: visible; }
        .receipt { position: absolute; left: 0; top: 0; border: 0; }
        .no-print { display: none !important; }
    }
</style>
@endpush
@section('content')
<div class="d-flex justify-content-center mb-3 no-print gap-2 flex-wrap">
    <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer me-1"></i>Print Browser</button>
    <form method="POST" action="{{ route('print.invoice', $invoice) }}" class="d-inline" onsubmit="return confirm('Kirim struk ke thermal printer?')">
        @csrf
        <button type="submit" class="btn btn-dark"><i class="bi bi-receipt me-1"></i>Print Thermal</button>
    </form>
    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-info text-white"><i class="bi bi-file-earmark-text me-1"></i>Lihat Invoice</a>
    <a href="{{ route('pos.terminal') }}" class="btn btn-success"><i class="bi bi-cart-plus me-1"></i>Transaksi Baru</a>
    <a href="{{ route('pos.sessions') }}" class="btn btn-outline-secondary"><i class="bi bi-list me-1"></i>Histori Sesi</a>
</div>

<div class="receipt">
    @if(!empty($appSettings['logo']))
    <div style="text-align:center;margin-bottom:4px;">
        <img src="{{ asset('storage/' . $appSettings['logo']) }}" alt="Logo" style="max-width:120px;max-height:60px;">
    </div>
    @endif
    <h5>{{ $appSettings['name'] ?? config('app.name') }}</h5>
    @if(!empty($appSettings['address']))
    <div class="sub">{{ $appSettings['address'] }}</div>
    @endif
    @if(!empty($appSettings['phone']))
    <div class="sub">{{ $appSettings['phone'] }}</div>
    @endif
    <div class="sub">Struk Penjualan POS</div>
    <hr>
    <div>No: <strong>{{ $invoice->invoice_number }}</strong></div>
    <div>Tgl: {{ $invoice->created_at->format('d/m/Y H:i') }}</div>
    <div>Kasir: {{ $invoice->posSession?->user?->name ?? '-' }}</div>
    <div>Pelanggan: {{ $invoice->customer?->name ?? 'Walk-in' }}</div>
    <hr>
    <table>
        @foreach($invoice->items as $item)
        <tr>
            <td colspan="2">{{ $item->description }}</td>
        </tr>
        <tr>
            <td>{{ $item->quantity }} × @money($item->unit_price)</td>
            <td class="right">@money($item->total)</td>
        </tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="right">@money($invoice->total_amount)</td></tr>
        @if($invoice->discount > 0)
        <tr><td>Diskon</td><td class="right">- @money($invoice->discount)</td></tr>
        @endif
        @foreach($invoice->voucherUsages as $vu)
        <tr><td>Voucher {{ $vu->voucher?->code }}</td><td class="right" style="color:#059669;">- @money($vu->discount_applied)</td></tr>
        @endforeach
        <tr class="total"><td>TOTAL</td><td class="right">@money($invoice->grand_total)</td></tr>
        @foreach($invoice->paymentRecords as $pr)
        <tr><td>Bayar ({{ $pr->paymentMethod?->payment ?? 'Cash' }})</td><td class="right">@money($pr->amount)</td></tr>
        @endforeach
        @if($change > 0)
        <tr class="change"><td>Kembali</td><td class="right">@money($change)</td></tr>
        @endif
    </table>
    <hr>
    <div class="sub">Terima kasih atas kunjungan Anda!</div>
    <div class="sub">{{ now()->format('d M Y H:i:s') }}</div>
</div>
@endsection
@push('scripts')
<script>
    // Auto-trigger print dialog on first load
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
</script>
@endpush
