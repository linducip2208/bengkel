<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Estimasi {{ $estimate->estimate_number }} — {{ $company['name'] ?? config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #166534 0%, #22c55e 100%); min-height: 100vh; padding: 1.5rem 1rem; }
        .container { max-width: 760px; }
        .card { border-radius: 16px; overflow: hidden; }
        .table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .4px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="mb-0">{{ $company['name'] ?? config('app.name') }}</h4>
                        <small class="text-muted">{{ $company['address'] }}@if($company['phone']) · {{ $company['phone'] }}@endif</small>
                    </div>
                    <div class="text-end">
                        <h5 class="mb-0">ESTIMASI</h5>
                        <code>{{ $estimate->estimate_number }}</code>
                        <div><span class="badge bg-{{ $estimate->statusColor() }} mt-1">v{{ $estimate->version }} — {{ $estimate->statusLabel() }}</span></div>
                    </div>
                </div>

                @if(session('success'))
                <div class="alert alert-success py-2"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>
                @endif
                @if(session('error'))
                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}</div>
                @endif

                <div class="table-responsive mb-3">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><td class="text-muted" width="130">Pelanggan</td><td><strong>{{ $customer['name'] ?? '-' }}</strong></td></tr>
                        <tr><td class="text-muted">Telp</td><td>{{ $customer['phone'] ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Alamat</td><td>{{ $customer['address'] ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Kendaraan</td><td>{{ $vehicle['number_plate'] ?? '-' }} — {{ trim(($vehicle['brand'] ?? '').' '.($vehicle['model'] ?? '')) ?: ($vehicle['type'] ?? '-') }} {{ $vehicle['year'] ? "({$vehicle['year']})" : '' }}</td></tr>
                        <tr><td class="text-muted">No. Service</td><td>{{ $service['number'] ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Tgl Estimasi</td><td>{{ $estimate->estimate_date?->format('d M Y') ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Berlaku Sampai</td><td>{{ $estimate->valid_until?->format('d M Y') ?? '-' }}</td></tr>
                    </table>
                </div>

                <h6 class="border-bottom pb-2">Rincian Pekerjaan</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Diskon</th>
                                <th class="text-end">Pajak</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($estimate->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $item->description }}
                                    @if($item->item_type === 'labor')<span class="badge bg-light text-dark">Jasa</span>@endif
                                </td>
                                <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="text-end">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">{{ (float) $item->discount > 0 ? 'Rp '.number_format((float) $item->discount, 0, ',', '.') : '-' }}</td>
                                <td class="text-end">{{ (float) $item->tax_amount > 0 ? 'Rp '.number_format((float) $item->tax_amount, 0, ',', '.') : '-' }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada item.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr><td colspan="6" class="text-end">Subtotal</td><td class="text-end">Rp {{ number_format((float) $estimate->subtotal, 0, ',', '.') }}</td></tr>
                            @if((float) $estimate->discount > 0)
                            <tr><td colspan="6" class="text-end">Diskon</td><td class="text-end">- Rp {{ number_format((float) $estimate->discount, 0, ',', '.') }}</td></tr>
                            @endif
                            @if((float) $estimate->tax_amount > 0)
                            <tr><td colspan="6" class="text-end">Pajak</td><td class="text-end">Rp {{ number_format((float) $estimate->tax_amount, 0, ',', '.') }}</td></tr>
                            @endif
                            <tr class="table-dark"><td colspan="6" class="text-end fw-bold">GRAND TOTAL</td><td class="text-end fw-bold">Rp {{ number_format((float) $estimate->grand_total, 0, ',', '.') }}</td></tr>
                        </tfoot>
                    </table>
                </div>

                @if($estimate->notes)
                <div class="mb-3">
                    <h6 class="border-bottom pb-2">Catatan</h6>
                    <p class="mb-0 small">{{ $estimate->notes }}</p>
                </div>
                @endif

                @if($estimate->terms)
                <div class="alert alert-light border small mb-3">
                    <strong>Syarat & Ketentuan:</strong> {{ $estimate->terms }}
                </div>
                @endif

                @if($estimate->status === \App\Models\ServiceEstimate::STATUS_APPROVED)
                <div class="alert alert-success text-center mb-0">
                    <i class="bi bi-check-circle-fill me-1"></i>Estimasi ini sudah <strong>disetujui</strong> pada {{ $estimate->approved_at?->format('d M Y H:i') }}.
                </div>
                @elseif($estimate->status === \App\Models\ServiceEstimate::STATUS_REJECTED)
                <div class="alert alert-secondary text-center mb-0">
                    <i class="bi bi-x-circle me-1"></i>Estimasi ini telah ditolak pada {{ $estimate->rejected_at?->format('d M Y H:i') }}. Kami akan menghubungi Anda untuk revisi.
                </div>
                @elseif($estimate->status === \App\Models\ServiceEstimate::STATUS_EXPIRED)
                <div class="alert alert-warning text-center mb-0">
                    <i class="bi bi-hourglass-split me-1"></i>Estimasi sudah kedaluwarsa. Hubungi kami untuk revisi harga terbaru.
                </div>
                @elseif($approvable)
                <div class="d-grid gap-2">
                    <form method="POST" action="{{ route('public.estimate.approve', $estimate->public_token) }}">
                        @csrf
                        <button class="btn btn-success btn-lg w-100"><i class="bi bi-check-circle me-1"></i>Setujui Estimasi</button>
                    </form>
                    <a href="#reject-form" class="btn btn-outline-danger" data-bs-toggle="collapse">Tolak Estimasi</a>
                    <div class="collapse" id="reject-form">
                        <form method="POST" action="{{ route('public.estimate.reject', $estimate->public_token) }}" class="card card-body">
                            @csrf
                            <label class="form-label small">Alasan penolakan (opsional)</label>
                            <textarea name="reason" rows="2" class="form-control form-control-sm mb-2" placeholder="Contoh: biaya masih terlalu tinggi"></textarea>
                            <button class="btn btn-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Ya, Tolak Estimasi</button>
                        </form>
                    </div>
                </div>
                @endif

                <div class="text-center mt-3">
                    <a href="{{ route('public.estimate.pdf', $estimate->public_token) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                    </a>
                </div>

                <p class="text-center text-muted small mt-3 mb-0">Dokumen ini adalah estimasi — bukan invoice dan bukan bukti pembayaran.</p>
            </div>
        </div>
    </div>
</body>
</html>
