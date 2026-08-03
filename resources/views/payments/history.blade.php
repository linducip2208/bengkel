@extends('layouts.app')
@section('title', 'Riwayat Pembayaran')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Riwayat Pembayaran: {{ $invoice->invoice_number }}</h4>
    <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Kembali ke Invoice</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div><strong>Pelanggan:</strong> {{ $invoice->customer->name ?? '-' }}</div>
        <div><strong>Total Invoice:</strong> @money($invoice->grand_total)</div>
    </div>
</div>

@if ($invoice->paymentRecords->count() > 0)
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Metode</th>
                <th>Ref</th>
                <th class="text-end">Jumlah</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->paymentRecords as $idx => $payment)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $payment->payment_date->format('d M Y H:i') }}</td>
                    <td>{{ $payment->paymentMethod?->name }}</td>
                    <td>{{ $payment->reference_number }}</td>
                    <td class="text-end">@money($payment->amount)</td>
                    <td>{{ $payment->notes }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="table-light">
            <tr>
                <td colspan="4" class="text-end"><strong>Total Dibayar</strong></td>
                <td class="text-end"><strong>@money($invoice->paymentRecords->sum('amount'))</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
@else
    <div class="text-center py-4 text-muted">Belum ada pembayaran.</div>
@endif
@endsection
