@extends('layouts.app')
@section('title', 'Detail Invoice')

@section('content')
<div class="d-print-none d-flex justify-content-between align-items-center mb-3">
    <h4>Invoice: {{ $invoice->invoice_number }}</h4>
    <div class="d-flex gap-2">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-file-earmark-pdf"></i> PDF
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width:280px;">
                <li><h6 class="dropdown-header">Pilih Template Invoice</h6></li>
                <li>
                    <div class="d-flex align-items-center px-3 py-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:13px;">Modern (Default)</div>
                            <small class="text-muted">Biru, berwarna, modern</small>
                        </div>
                        <div class="d-flex gap-1 ms-2">
                            <a href="{{ route('invoices.preview', ['invoice' => $invoice, 'template' => 'modern']) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Preview"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', ['invoice' => $invoice, 'template' => 'modern']) }}" class="btn btn-sm btn-outline-secondary" title="Download PDF"><i class="bi bi-download"></i></a>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
                <li>
                    <div class="d-flex align-items-center px-3 py-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:13px;">Classic Formal</div>
                            <small class="text-muted">Hitam-putih, formal, stempel</small>
                        </div>
                        <div class="d-flex gap-1 ms-2">
                            <a href="{{ route('invoices.preview', ['invoice' => $invoice, 'template' => 'classic']) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Preview"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', ['invoice' => $invoice, 'template' => 'classic']) }}" class="btn btn-sm btn-outline-secondary" title="Download PDF"><i class="bi bi-download"></i></a>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
                <li>
                    <div class="d-flex align-items-center px-3 py-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:13px;">Minimal Clean</div>
                            <small class="text-muted">Bersih, banyak whitespace</small>
                        </div>
                        <div class="d-flex gap-1 ms-2">
                            <a href="{{ route('invoices.preview', ['invoice' => $invoice, 'template' => 'minimal']) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Preview"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', ['invoice' => $invoice, 'template' => 'minimal']) }}" class="btn btn-sm btn-outline-secondary" title="Download PDF"><i class="bi bi-download"></i></a>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
                <li>
                    <div class="d-flex align-items-center px-3 py-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:13px;">Thermal / Struk</div>
                            <small class="text-muted">Ukuran kecil, monospace</small>
                        </div>
                        <div class="d-flex gap-1 ms-2">
                            <a href="{{ route('invoices.preview', ['invoice' => $invoice, 'template' => 'thermal']) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Preview"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.pdf', ['invoice' => $invoice, 'template' => 'thermal']) }}" class="btn btn-sm btn-outline-secondary" title="Download PDF"><i class="bi bi-download"></i></a>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <button type="button" class="btn btn-outline-dark" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <a href="{{ route('invoices.sendWA', $invoice) }}" class="btn btn-outline-success" target="_blank"><i class="bi bi-whatsapp"></i> Kirim WA</a>
        @if ($remaining > 0)
            <a href="{{ route('payments.create', $invoice) }}" class="btn btn-primary"><i class="bi bi-cash-coin"></i> Catat Pembayaran</a>
        @endif
        @if ($invoice->status !== 'full_paid')
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning"><i class="bi bi-pencil"></i> Edit</a>
        @endif
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>

{{-- Print-only header --}}
<div class="d-none d-print-block" style="border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:15px;">
    <table style="width:100%"><tr>
        <td width="80" style="vertical-align:top;">
            @if(!empty($settings['logo']))
                <img src="{{ asset('storage/' . $settings['logo']) }}" style="max-width:70px;max-height:70px;">
            @endif
        </td>
        <td style="vertical-align:top;">
            <h2 style="margin:0;font-size:20px;">{{ $settings['name'] ?? config('app.name') }}</h2>
            <div style="font-size:11px;color:#555;">{{ $settings['address'] ?? '' }} | Telp: {{ $settings['phone'] ?? '-' }}</div>
            <div style="font-size:11px;color:#555;">Email: {{ $settings['email'] ?? '-' }}</div>
        </td>
        <td style="text-align:right;vertical-align:top;">
            <h3 style="margin:0;font-size:22px;color:#1a56db;">INVOICE</h3>
            <div style="font-size:14px;font-weight:bold;">{{ $invoice->invoice_number }}</div>
        </td>
    </tr></table>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">Pelanggan</small>
                        <div><strong>{{ $invoice->customer->name ?? '-' }}</strong></div>
                        <div>{{ $invoice->customer->phone ?? '' }}</div>
                        <div>{{ $invoice->customer->address ?? '' }}</div>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted">Status</small>
                        <div>
                            @if ($invoice->status === 'full_paid')
                                <span class="badge bg-success fs-6">Lunas</span>
                            @elseif ($invoice->status === 'half_paid')
                                <span class="badge bg-warning text-dark fs-6">Dibayar Sebagian</span>
                            @else
                                <span class="badge bg-danger fs-6">Belum Dibayar</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><small class="text-muted">No. Invoice</small><div><strong>{{ $invoice->invoice_number }}</strong></div></div>
                    <div class="col-4"><small class="text-muted">Tanggal</small><div>{{ $invoice->invoice_date->format('d M Y') }}</div></div>
                    <div class="col-4"><small class="text-muted">Tipe</small><div><span class="badge bg-secondary">{{ $invoice->invoice_type }}</span></div></div>
                </div>
                @php $vehicle = $invoice->service?->vehicle ?? $invoice->sale?->vehicle; @endphp
                <div class="row mb-3">
                    <div class="col-3"><small class="text-muted">Jenis Kendaraan</small><div><strong>{{ $vehicle->model_name ?? '-' }}</strong></div></div>
                    <div class="col-3"><small class="text-muted">No. Plat</small><div><strong>{{ $vehicle->number_plate ?? '-' }}</strong></div></div>
                    <div class="col-3"><small class="text-muted">KM</small><div>{{ number_format($invoice->service?->jobcardDetail?->odometer_in ?? $vehicle->odometer ?? 0, 0, ',', '.') }}</div></div>
                    <div class="col-3"><small class="text-muted">No. Service</small><div>{{ $invoice->service?->job_no ?? '-' }}</div></div>
                </div>

                <table class="table table-bordered mt-3">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Deskripsi</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">@money($item->unit_price)</td>
                                <td class="text-end">@money($item->total_price)</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end">Subtotal</td>
                            <td class="text-end">@money($invoice->subtotal)</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end">Diskon</td>
                            <td class="text-end text-danger">- @money($invoice->discount)</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end">Pajak</td>
                            <td class="text-end">@money($invoice->tax_amount)</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                            <td class="text-end"><strong>@money($invoice->grand_total)</strong></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end text-success">Total Dibayar</td>
                            <td class="text-end text-success">@money($totalPaid)</td>
                        </tr>
                        <tr class="{{ $remaining > 0 ? 'table-danger' : 'table-success' }}">
                            <td colspan="4" class="text-end"><strong>Sisa</strong></td>
                            <td class="text-end"><strong>@money($remaining)</strong></td>
                        </tr>
                    </tfoot>
                </table>

                @if ($invoice->notes)
                    <div class="mt-2"><small class="text-muted">Catatan:</small> {{ $invoice->notes }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body text-center">
                <h1 class="fw-bold mb-0 {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                    @money($remaining)
                </h1>
                <small class="text-muted">Total Harus Dibayar</small>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-2">
                        <div class="fw-bold text-success">@money($totalPaid)</div>
                        <small class="text-muted">Total Dibayar</small>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center py-2">
                        <div class="fw-bold">@money($invoice->dp_amount ?? 0)</div>
                        <small class="text-muted">Down Payment</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><strong>Riwayat Pembayaran</strong></div>
            <div class="card-body p-0">
                @if ($invoice->paymentRecords->count() > 0)
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->paymentRecords as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td>{{ $payment->paymentMethod?->name }}</td>
                                    <td class="text-end">@money($payment->amount)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="p-3 text-muted text-center">Belum ada pembayaran.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
