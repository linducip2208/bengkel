{{-- Tab: ESTIMASI — quotation builder + lifecycle (never payment terminology) --}}
@php
    $activeEstimate = $estimateSummary['estimate'] ?? null;
    $allEstimates = $service->estimates->sortByDesc('version');
    $canCreate = auth()->user()?->can('estimates.create');
    $canUpdate = auth()->user()?->can('estimates.update');
    $canSend = auth()->user()?->can('estimates.send');
    $canRevise = auth()->user()?->can('estimates.revise');
    $canOverride = auth()->user()?->can('estimates.override');
    $canConvert = auth()->user()?->can('estimates.convert_invoice');
    $company = $activeEstimate?->snapshotCompany() ?? app(\App\Services\SettingsService::class)->getCompanyInfo();
    $approvalSummary = $estimateSummary['approval_summary'] ?? ['approved' => 0, 'rejected' => 0, 'pending' => 0];
@endphp
<div class="tab-pane fade" id="tab-estimate">
    <div class="card">
        <div class="card-body">

            {{-- ============================ LIVE ESTIMATE ============================ --}}
            @if($activeEstimate)
            <div class="estimate-document-header border-bottom pb-3 mb-4">
                <div class="row g-3 align-items-start">
                    <div class="col-md-7">
                        <div class="text-uppercase small text-muted fw-semibold">{{ $company['name'] ?? config('app.name') }}</div>
                        <h4 class="mb-1">Estimasi Servis</h4>
                        <div class="small text-muted">{{ $company['address'] ?? '' }} · {{ $company['phone'] ?? '-' }} · {{ $company['email'] ?? '-' }}</div>
                        @if(!empty($company['tax_id']))<div class="small text-muted">NPWP: {{ $company['tax_id'] }}</div>@endif
                    </div>
                    <div class="col-md-5 text-md-end">
                        <div class="text-muted small">Nomor Estimasi</div>
                        <div class="h5 mb-1">{{ $activeEstimate->estimate_number }}</div>
                        <span class="badge bg-{{ $activeEstimate->statusColor() }}">{{ $activeEstimate->statusLabel() }}</span>
                        <div class="small text-muted mt-2">Tanggal: {{ $activeEstimate->estimate_date?->format('d M Y') ?? '-' }} · Berlaku sampai: {{ $activeEstimate->valid_until?->format('d M Y') ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h6 class="mb-1"><i class="fas fa-file-signature me-2 text-warning"></i>
                        {{ $activeEstimate->estimate_number }}
                        <span class="badge bg-{{ $activeEstimate->statusColor() }} ms-1">v{{ $activeEstimate->version }} — {{ $activeEstimate->statusLabel() }}</span>
                    </h6>
                    <small class="text-muted">
                        Tgl: {{ $activeEstimate->estimate_date?->format('d M Y') ?? '-' }} ·
                        Berlaku sampai: <span class="{{ $activeEstimate->isExpiredByDate() ? 'text-danger fw-bold' : '' }}">{{ $activeEstimate->valid_until?->format('d M Y') ?? '-' }}</span>
                    </small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('estimates.preview', $activeEstimate) }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-eye me-1"></i> Preview</a>
                    <a href="{{ route('estimates.pdf', $activeEstimate) }}" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> Download PDF</a>
                    <a href="{{ route('estimates.print', $activeEstimate) }}" class="btn btn-sm btn-outline-dark" target="_blank"><i class="fas fa-print me-1"></i> Print</a>
                    @if($canSend && in_array($activeEstimate->status, [\App\Models\ServiceEstimate::STATUS_DRAFT, \App\Models\ServiceEstimate::STATUS_SENT, \App\Models\ServiceEstimate::STATUS_WAITING_APPROVAL, \App\Models\ServiceEstimate::STATUS_REJECTED, \App\Models\ServiceEstimate::STATUS_EXPIRED], true))
                    <form action="{{ route('estimates.send-wa', $activeEstimate) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-success"><i class="fab fa-whatsapp me-1"></i> Kirim WhatsApp</button>
                    </form>
                    <form action="{{ route('estimates.send-email', $activeEstimate) }}" method="POST" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-envelope me-1"></i> Kirim Email</button>
                    </form>
                    @endif
                    @if($canRevise && $activeEstimate->status !== \App\Models\ServiceEstimate::STATUS_DRAFT)
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reviseEstimateModal"><i class="fas fa-code-branch me-1"></i> Buat Revisi</button>
                    @endif
                    @if($canOverride && $activeEstimate->status === \App\Models\ServiceEstimate::STATUS_WAITING_APPROVAL)
                    <button type="button" class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#overrideApproveModal"><i class="fas fa-user-shield me-1"></i> Override Manager</button>
                    @endif
                    @if($canConvert && $activeEstimate->status === \App\Models\ServiceEstimate::STATUS_APPROVED)
                    <form action="{{ route('estimates.convert-invoice', $activeEstimate) }}" method="POST" class="d-inline" onsubmit="return confirm('Buat Invoice dari estimasi ini? Estimasi akan menjadi dokumen historis.')">
                        @csrf
                        <button class="btn btn-sm btn-primary"><i class="fas fa-file-invoice-dollar me-1"></i> Buat Invoice dari Estimasi</button>
                    </form>
                    @endif
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100">
                        <small class="text-muted text-uppercase fw-bold">Customer</small>
                        @php $snapCust = $activeEstimate->snapshotCustomer(); @endphp
                        <div class="fw-semibold">{{ $snapCust['name'] ?? '-' }}</div>
                        <small class="text-muted">{{ $snapCust['phone'] ?? '-' }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100">
                        <small class="text-muted text-uppercase fw-bold">Kendaraan</small>
                        @php $snapVeh = $activeEstimate->snapshotVehicle(); @endphp
                        <div class="fw-semibold">{{ $snapVeh['number_plate'] ?? '-' }}</div>
                        <small class="text-muted">{{ trim(($snapVeh['brand'] ?? '').' '.($snapVeh['model'] ?? '')) }} {{ $snapVeh['year'] ? "· {$snapVeh['year']}" : '' }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100">
                        <small class="text-muted text-uppercase fw-bold">Service / WO</small>
                        @php $snapSvc = $activeEstimate->snapshotService(); @endphp
                        <div class="fw-semibold">{{ $snapSvc['number'] ?? $service->job_no }}</div>
                        <small class="text-muted">{{ $snapSvc['title'] ?? $service->title }}</small>
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong><i class="fas fa-user-check me-1 text-primary"></i>Persetujuan Customer per Pekerjaan</strong>
                    <span class="small text-muted">Hanya pekerjaan disetujui yang dapat dilanjutkan</span>
                </div>
                @forelse($activeEstimate->groups as $group)
                    @php
                        $decisionColor = $group->customer_decision === \App\Models\ServiceEstimateGroup::DECISION_APPROVED ? 'success' : ($group->customer_decision === \App\Models\ServiceEstimateGroup::DECISION_REJECTED ? 'danger' : 'warning');
                    @endphp
                    <div class="d-flex justify-content-between align-items-center border-top py-2 small gap-2">
                        <span><strong>{{ $group->workPackage?->title ?? $group->title }}</strong>@if($group->finding)<span class="text-muted"> · {{ $group->finding->finding_number }}</span>@endif</span>
                        <span class="badge bg-{{ $decisionColor }}{{ $decisionColor === 'warning' ? ' text-dark' : '' }}">{{ \App\Models\ServiceEstimateGroup::DECISION_LABELS[$group->customer_decision] ?? $group->customer_decision }}</span>
                    </div>
                @empty
                    <div class="small text-muted">Belum ada pekerjaan yang dikelompokkan pada estimasi ini.</div>
                @endforelse
            </div>

            @if($activeEstimate->groups->isNotEmpty())
            <div class="row g-3 mb-4">
                @foreach($activeEstimate->groups as $group)
                @php $finding = $group->finding; $decision = $group->customer_decision; @endphp
                <article class="col-12">
                    <div class="estimate-group-card border rounded-3 p-3">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="small text-muted">Pekerjaan {{ $loop->iteration }}</div>
                                <h6 class="mb-1">{{ $group->title }}</h6>
                                <div class="small text-muted">{{ $finding ? 'Temuan '.$finding->finding_number.' · '.$finding->title : 'Pekerjaan Tambahan / Manual' }}</div>
                            </div>
                            <span class="badge bg-{{ $decision === \App\Models\ServiceEstimateGroup::DECISION_APPROVED ? 'success' : ($decision === \App\Models\ServiceEstimateGroup::DECISION_REJECTED ? 'danger' : 'warning text-dark') }}">{{ \App\Models\ServiceEstimateGroup::DECISION_LABELS[$decision] ?? $decision }}</span>
                        </div>
                        @if($finding)
                        <div class="row g-2 small bg-light rounded p-2 my-3">
                            <div class="col-md-3"><span class="text-muted d-block">Status Pemeriksaan</span>{{ \App\Models\ServiceFinding::SEVERITY_LABELS[$finding->severity] ?? $finding->severity }}</div>
                            @if($finding->measurement_value !== null)<div class="col-md-3"><span class="text-muted d-block">Hasil Pemeriksaan</span>{{ $finding->measurement_value }} {{ $finding->measurement_unit }}</div>@endif
                            @if($finding->recommendation)<div class="col-md-6"><span class="text-muted d-block">Rekomendasi</span>{{ $finding->recommendation }}</div>@endif
                        </div>
                        @endif
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-2">
                                <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Pajak</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                @foreach($group->items as $item)
                                <tr><td><span class="badge bg-light text-dark me-1">{{ [\App\Models\ServiceEstimateItem::TYPE_PART => 'Parts', \App\Models\ServiceEstimateItem::TYPE_LABOR => 'Jasa', \App\Models\ServiceEstimateItem::TYPE_OTHER => 'Lainnya'][$item->item_type] ?? 'Lainnya' }}</span>{{ $item->description }}</td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">@include('partials.rupiah', ['amount' => $item->unit_price])</td><td class="text-end">{{ (float) $item->discount > 0 ? '- ' : '' }}@include('partials.rupiah', ['amount' => $item->discount])</td><td class="text-end">@include('partials.rupiah', ['amount' => $item->tax_amount])</td><td class="text-end fw-semibold">@include('partials.rupiah', ['amount' => $item->line_total])</td></tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 small"><span>Estimasi Waktu: <strong>{{ $group->standard_minutes }} menit</strong></span><span>Subtotal Pekerjaan: <strong>@include('partials.rupiah', ['amount' => $group->grand_total])</strong></span></div>
                    </div>
                </article>
                @endforeach
            </div>
            @endif

            <div class="estimate-summary border-top pt-3 mb-3">
                <h6>Ringkasan Estimasi</h6>
                <div class="row justify-content-end"><div class="col-md-5">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><strong>@include('partials.rupiah', ['amount' => $activeEstimate->subtotal])</strong></div>
                    <div class="d-flex justify-content-between"><span>Diskon</span><strong>@include('partials.rupiah', ['amount' => $activeEstimate->discount])</strong></div>
                    <div class="d-flex justify-content-between"><span>Pajak</span><strong>@include('partials.rupiah', ['amount' => $activeEstimate->tax_amount])</strong></div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2 fs-5"><span>Total Estimasi</span><strong>@include('partials.rupiah', ['amount' => $activeEstimate->grand_total])</strong></div>
                    @if($activeEstimate->groups->isNotEmpty())
                    <div class="d-flex justify-content-between text-success mt-2"><span>Total Disetujui</span><strong>@include('partials.rupiah', ['amount' => $approvalSummary['approved']])</strong></div>
                    <div class="d-flex justify-content-between text-danger"><span>Total Ditolak</span><strong>@include('partials.rupiah', ['amount' => $approvalSummary['rejected']])</strong></div>
                    <div class="d-flex justify-content-between text-warning"><span>Total Menunggu</span><strong>@include('partials.rupiah', ['amount' => $approvalSummary['pending']])</strong></div>
                    @endif
                </div></div>
            </div>

            {{-- Item summary table (desktop table, mobile cards) --}}
            <div class="d-none">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:4%">#</th>
                            <th>Tipe</th>
                            <th>Deskripsi</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Pajak</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeEstimate->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="badge bg-light text-dark">{{ ['part' => 'Part', 'labor' => 'Jasa', 'other' => 'Manual'][$item->item_type] ?? $item->item_type }}</span></td>
                            <td>{{ $item->description }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">@include('partials.rupiah', ['amount' => $item->unit_price])</td>
                            <td class="text-end">@if((float) $item->discount > 0)@include('partials.rupiah', ['amount' => $item->discount])@else - @endif</td>
                            <td class="text-end">@if((float) $item->tax_amount > 0)@include('partials.rupiah', ['amount' => $item->tax_amount])@else - @endif</td>
                            <td class="text-end fw-semibold">@include('partials.rupiah', ['amount' => $item->line_total])</td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-3">Estimasi kosong.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr><td colspan="7" class="text-end">Subtotal</td><td class="text-end">@include('partials.rupiah', ['amount' => $activeEstimate->subtotal])</td></tr>
                        @if((float) $activeEstimate->discount > 0)
                        <tr><td colspan="7" class="text-end">Diskon</td><td class="text-end">- @include('partials.rupiah', ['amount' => $activeEstimate->discount])</td></tr>
                        @endif
                        @if((float) $activeEstimate->tax_amount > 0)
                        <tr><td colspan="7" class="text-end">Pajak</td><td class="text-end">@include('partials.rupiah', ['amount' => $activeEstimate->tax_amount])</td></tr>
                        @endif
                        <tr class="table-dark"><td colspan="7" class="text-end fw-bold">GRAND TOTAL</td><td class="text-end fw-bold">@include('partials.rupiah', ['amount' => $activeEstimate->grand_total])</td></tr>
                    </tfoot>                </table>
            </div>
            {{-- Mobile item cards --}}
            <div class="d-none">
                @forelse($activeEstimate->items as $item)
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-light text-dark mb-1">{{ ['part' => 'Part', 'labor' => 'Jasa', 'other' => 'Manual'][$item->item_type] ?? $item->item_type }}</span>
                        <small class="text-muted">Qty {{ $item->quantity }}</small>
                    </div>
                    <div class="fw-semibold">{{ $item->description }}</div>
                    <div class="d-flex justify-content-between small"><span class="text-muted">Harga</span><span>@include('partials.rupiah', ['amount' => $item->unit_price])</span></div>
                    <div class="d-flex justify-content-between small fw-semibold"><span>Total</span><span>@include('partials.rupiah', ['amount' => $item->line_total])</span></div>
                </div>
                @empty
                <p class="text-center text-muted py-2">Estimasi kosong.</p>
                @endforelse
            </div>

            @if($activeEstimate->notes)
            <div class="border rounded p-2 mb-2"><small class="text-muted text-uppercase fw-bold">Catatan</small><p class="mb-0 small">{{ $activeEstimate->notes }}</p></div>
            @endif
            @if($activeEstimate->terms)
            <div class="alert alert-light border small mb-0"><strong>Syarat:</strong> {{ $activeEstimate->terms }}</div>
            @endif
            @else
            <div class="text-center py-4">
                <i class="fas fa-file-signature fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-1">Belum ada estimasi aktif untuk servis ini.</p>
                <small class="text-muted d-block mb-3">Belum ada rekomendasi dari pemeriksaan. Anda tetap dapat membuat estimasi langsung.</small>
            </div>
            @endif

            @if(($canCreate || $canUpdate) && (! $activeEstimate || $activeEstimate->isEditable()))
                @include('estimates.partials.builder', ['builderEstimate' => $activeEstimate, 'availablePackages' => $service->workPackages->whereIn('status', ['draft', 'proposed', 'approved']), 'canOverridePrice' => auth()->user()?->can('pos.price_override')])
            @endif

            {{-- ============================ VERSION HISTORY ============================ --}}
            @if($allEstimates->count() > 0)
            <div class="border-top pt-3 mt-3">
                <h6 class="mb-2"><i class="fas fa-history me-1"></i> Riwayat Versi Estimasi</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-light"><tr><th>No</th><th>Versi</th><th>Status</th><th>Tanggal</th><th>Berlaku Sampai</th><th class="text-end">Grand Total</th><th>Aksi</th></tr></thead>
                        <tbody>
                            @foreach($allEstimates as $est)
                            <tr class="{{ $est->id === ($activeEstimate?->id) ? 'table-warning' : '' }}">
                                <td>{{ $est->estimate_number }}</td>
                                <td>v{{ $est->version }}@if($est->previous_estimate_id) <i class="fas fa-code-branch text-muted small" title="revisi"></i>@endif</td>
                                <td><span class="badge bg-{{ $est->statusColor() }}">{{ $est->statusLabel() }}</span></td>
                                <td>{{ $est->estimate_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ $est->valid_until?->format('d/m/Y') ?? '-' }}</td>
                                <td class="text-end">@include('partials.rupiah', ['amount' => $est->grand_total])</td>
                                <td>
                                    <a href="{{ route('estimates.preview', $est) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-0 px-1" title="Preview"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('estimates.pdf', $est) }}" class="btn btn-xs btn-outline-danger py-0 px-1" title="PDF"><i class="fas fa-file-pdf"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- ============================ RECONCILIATION ============================ --}}
            @if(($estimateSummary['invoice'] ?? null) && ($estimateSummary['approved_estimate'] ?? 0) > 0)
            <div class="border-top pt-3 mt-3">
                <h6 class="mb-2"><i class="fas fa-scale-balanced me-1"></i> Rekonsiliasi Estimasi vs Invoice</h6>
                <div class="row text-center g-2">
                    <div class="col-6 col-md"><div class="border rounded p-2"><small class="text-muted d-block">Estimasi Disetujui</small><strong>@include('partials.rupiah', ['amount' => $estimateSummary['approved_estimate']])</strong></div></div>
                    <div class="col-6 col-md"><div class="border rounded p-2"><small class="text-muted d-block">Invoice</small><strong>@include('partials.rupiah', ['amount' => $estimateSummary['invoice_amount']])</strong></div></div>
                    <div class="col-6 col-md"><div class="border rounded p-2"><small class="text-muted d-block">Selisih</small><strong class="{{ abs($estimateSummary['variance']) < 0.01 ? 'text-success' : 'text-danger' }}">@include('partials.rupiah', ['amount' => $estimateSummary['variance']])</strong></div></div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
    .estimate-group-card { background: #fff; border-color: #e5e7eb !important; transition: box-shadow .2s ease, transform .2s ease; }
    .estimate-group-card:hover { box-shadow: 0 8px 22px rgba(15, 23, 42, .08); transform: translateY(-1px); }
    .estimate-group-card table th { color: #64748b; font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; }
    @media (max-width: 640px) { .estimate-document-header .text-md-end { text-align: left !important; } .estimate-group-card { padding: 1rem !important; } }
    @media print { .estimate-document-header, .estimate-group-card { break-inside: avoid; } }
</style>
@endpush

{{-- ============================ MODALS ============================ --}}
@if($canRevise && $activeEstimate && $activeEstimate->status !== \App\Models\ServiceEstimate::STATUS_DRAFT)
<div class="modal fade" id="reviseEstimateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('estimates.revise', $activeEstimate) }}">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-code-branch me-1"></i> Buat Revisi {{ $activeEstimate->estimate_number }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Versi {{ $activeEstimate->version }} akan disimpan apa adanya sebagai dokumen historis dan versi baru (draft) akan dibuat. Silakan edit item pada form estimasi setelah revisi dibuat.</p>
                <label class="form-label small">Alasan revisi <span class="text-danger">*</span></label>
                <textarea name="revision_reason" rows="2" class="form-control form-control-sm" required placeholder="Contoh: ditemukan kerusakan tambahan rack steering"></textarea>
                <input type="hidden" name="use_current_items" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-warning">Buat Revisi</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($canOverride && $activeEstimate && $activeEstimate->status === \App\Models\ServiceEstimate::STATUS_WAITING_APPROVAL)
<div class="modal fade" id="overrideApproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('estimates.override-approve', $activeEstimate) }}">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-user-shield me-1"></i> Override Persetujuan (Manager)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small mb-2">Override menyetujui estimasi tanpa konfirmasi customer. Semua aktivitas dicatat pada audit log.</div>
                <label class="form-label small">Alasan <span class="text-danger">*</span></label>
                <textarea name="reason" rows="2" class="form-control form-control-sm" required placeholder="Wajib diisi minimal 5 karakter"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-dark">Setujui (Override)</button>
            </div>
        </form>
    </div>
</div>
@endif
