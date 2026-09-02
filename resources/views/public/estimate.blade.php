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

                @if($estimate->groups->isNotEmpty())
                {{-- ============================ PER WORK PACKAGE DECISION ============================ --}}
                <h6 class="border-bottom pb-2">Pilih Pekerjaan yang Disetujui</h6>
                @php
                    $approvedAmount = (float) $estimate->groups->where('customer_decision', 'approved')->sum('grand_total');
                    $rejectedAmount = (float) $estimate->groups->where('customer_decision', 'rejected')->sum('grand_total');
                    $totalAmount = (float) $estimate->groups->sum('grand_total');
                @endphp
                <form method="POST" action="{{ route('public.estimate.decide', $estimate->public_token) }}" id="decideForm">
                    @csrf
                    @foreach($estimate->groups as $group)
                    <div class="border rounded p-3 mb-2 {{ $group->customer_decision === 'rejected' ? 'opacity-75' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $group->title }}</strong>
                                @if($group->severity_snapshot === 'critical')
                                    <span class="badge bg-danger">🔴 dari checklist kritis</span>
                                @elseif($group->severity_snapshot === 'repair_required')
                                    <span class="badge bg-warning text-dark">🟠 dari checklist perlu perbaikan</span>
                                @elseif($group->severity_snapshot === 'attention')
                                    <span class="badge bg-warning bg-opacity-50 text-dark">🟡 dari checklist perlu perhatian</span>
                                @else
                                    <span class="badge bg-secondary">manual</span>
                                @endif
                                @if($group->standard_minutes > 0)<small class="text-muted d-block">Standar waktu: {{ $group->standard_minutes }} menit</small>@endif
                            </div>
                            <strong>Rp {{ number_format((float) $group->grand_total, 0, ',', '.') }}</strong>
                        </div>
                        @if($group->items->isNotEmpty())
                        <details class="small mt-1">
                            <summary class="text-muted">Rincian item</summary>
                            @foreach($group->items as $item)
                            <div class="d-flex justify-content-between"><span>{{ ['labor' => 'Jasa', 'part' => 'Part', 'other' => 'Lain'][$item->item_type] ?? '' }}: {{ $item->description }} × {{ $item->quantity }}</span><span>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</span></div>
                            @endforeach
                        </details>
                        @endif

                        @if($group->customer_decision === 'pending' && $approvable)
                        <div class="d-flex gap-2 mt-2">
                            <label class="btn btn-success btn-sm mb-0">
                                <input type="radio" name="decisions[{{ $group->id }}][decision]" value="approved" class="me-1" data-amount="{{ $group->grand_total }}" required> SETUJUI
                            </label>
                            <label class="btn btn-outline-danger btn-sm mb-0">
                                <input type="radio" name="decisions[{{ $group->id }}][decision]" value="rejected" class="me-1" data-amount="0"> TOLAK
                            </label>
                            <input type="hidden" name="decisions[{{ $group->id }}][group_id]" value="{{ $group->id }}">
                        </div>
                        @else
                        <div class="mt-2">
                            @if($group->customer_decision === 'approved')<span class="badge bg-success"><i class="bi bi-check me-1"></i>Disetujui</span>
                            @elseif($group->customer_decision === 'rejected')<span class="badge bg-danger"><i class="bi bi-x me-1"></i>Ditolak</span>
                            @else<span class="badge bg-warning text-dark">Menunggu</span>@endif
                        </div>
                        @endif
                    </div>
                    @endforeach

                    @if($approvable)
                    <div class="card card-body bg-light mb-3 small">
                        <div class="d-flex justify-content-between"><span>Total Estimate:</span><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></div>
                        <div class="d-flex justify-content-between text-success"><span>Disetujui:</span><strong id="approvedSum">Rp {{ number_format($approvedAmount, 0, ',', '.') }}</strong></div>
                        <div class="d-flex justify-content-between text-danger"><span>Ditolak:</span><strong id="rejectedSum">Rp {{ number_format($rejectedAmount, 0, ',', '.') }}</strong></div>
                        <button class="btn btn-success w-100 mt-2" id="confirmDecisions"><i class="bi bi-check2-all me-1"></i>KONFIRMASI KEPUTUSAN</button>
                        <small class="text-muted mt-1 d-block">Keputusan bersifat final. Pekerjaan yang ditolak tidak akan dikerjakan dan tidak ditagihkan.</small>
                    </div>
                    @endif
                </form>
                @else
                {{-- Legacy: whole-document approval --}}
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
                @endif

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

                @if($estimate->groups->isNotEmpty() && $estimate->status === \App\Models\ServiceEstimate::STATUS_APPROVED)
                <div class="alert alert-success text-center mb-0">
                    <i class="bi bi-check-circle-fill me-1"></i>Semua pekerjaan <strong>disetujui</strong> pada {{ $estimate->approved_at?->format('d M Y H:i') }}.
                </div>
                @elseif($estimate->groups->isNotEmpty() && $estimate->status === \App\Models\ServiceEstimate::STATUS_PARTIALLY_APPROVED)
                <div class="alert alert-info text-center mb-0">
                    <i class="bi bi-check2-all me-1"></i>Pekerjaan <strong>disetujui sebagian</strong>: Rp {{ number_format((float) $estimate->approved_total, 0, ',', '.') }}. Pekerjaan yang ditolak tidak akan dikerjakan.
                </div>
                @elseif($estimate->status === \App\Models\ServiceEstimate::STATUS_APPROVED)
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
                @elseif($approvable && $estimate->groups->isEmpty())
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

    <script>
    (function () {
        function refresh() {
            var approved = 0, rejected = 0, decided = 0, radios = document.querySelectorAll('#decideForm input[type=radio]');
            radios.forEach(function (r) {
                if (! r.checked) { return; }
                decided++;
                if (r.value === 'approved') { approved += parseFloat(r.getAttribute('data-amount') || '0'); }
            });
            var total = document.querySelectorAll('#decideForm input[type=radio][value=approved]').length;
            var a = document.getElementById('approvedSum'), rj = document.getElementById('rejectedSum');
            if (a) { a.textContent = 'Rp ' + approved.toLocaleString('id-ID'); }
            var confirmBtn = document.getElementById('confirmDecisions');
            if (confirmBtn) { confirmBtn.disabled = decided < total; }
        }
        document.querySelectorAll('#decideForm input[type=radio]').forEach(function (r) {
            r.addEventListener('change', refresh);
        });
        refresh();
    })();
    </script>
</body>
</html>
