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
                    @if($activeEstimate->status !== \App\Models\ServiceEstimate::STATUS_DRAFT)
                    <a href="{{ route('estimates.preview', $activeEstimate) }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-eye me-1"></i> Preview</a>
                    <a href="{{ route('estimates.pdf', $activeEstimate) }}" class="btn btn-sm btn-outline-danger"><i class="fas fa-file-pdf me-1"></i> Download PDF</a>
                    <a href="{{ route('estimates.print', $activeEstimate) }}" class="btn btn-sm btn-outline-dark" target="_blank"><i class="fas fa-print me-1"></i> Print</a>
                    @endif
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
                                <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Harga</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                @foreach($group->items as $item)
                                <tr><td><span class="badge bg-light text-dark me-1">{{ [\App\Models\ServiceEstimateItem::TYPE_PART => 'Parts', \App\Models\ServiceEstimateItem::TYPE_LABOR => 'Jasa', \App\Models\ServiceEstimateItem::TYPE_OTHER => 'Lainnya'][$item->item_type] ?? 'Lainnya' }}</span>{{ $item->description }}</td><td class="text-center">{{ $item->quantity }}</td><td class="text-end">@include('partials.rupiah', ['amount' => $item->unit_price])</td><td class="text-end fw-semibold">@include('partials.rupiah', ['amount' => $item->line_total])</td></tr>
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
                <p class="text-muted">Belum ada estimasi aktif untuk servis ini.</p>
            </div>
            @endif

            @if($canCreate || $canUpdate)
            {{-- ============================ BUILDER ============================ --}}
            <div class="border-top pt-3 mt-3">
                <h6 class="mb-3">{{ $activeEstimate?->isEditable() ? 'Edit Estimasi' : 'Buat Estimasi Baru' }}</h6>

                {{-- Source work packages (checklist/finding evidence) --}}
                @if($service->workPackages->whereIn('status', ['draft', 'proposed', 'approved'])->isNotEmpty())
                <div class="border rounded p-2 mb-3 bg-light">
                    <strong class="small"><i class="fas fa-briefcase me-1"></i> Sumber Pekerjaan (Work Package)</strong>
                    <small class="text-muted d-block mb-2">Pekerjaan yang dipilih menjadi grup estimasi dengan badge sumber — customer menyetujui per pekerjaan.</small>
                    <div class="row g-1">
                        @foreach($service->workPackages->whereIn('status', ['draft', 'proposed', 'approved']) as $wp)
                        @php
                            $wpTotals = $wp->computeTotals();
                            $selected = $activeEstimate && $activeEstimate->groups->contains('service_work_package_id', $wp->id);
                        @endphp
                        <div class="col-md-6">
                            <label class="border rounded p-2 d-flex justify-content-between align-items-center h-100 mb-0 bg-white {{ $selected ? 'border-warning' : '' }}" style="cursor:pointer">
                                <span class="small">
                                    <input type="checkbox" name="packages[]" value="{{ $wp->id }}" class="me-1" {{ $selected ? 'checked' : '' }}>
                                    <strong>{{ $wp->title }}</strong>
                                    @if($wp->finding)
                                        <span class="badge {{ $wp->finding->severity === 'critical' ? 'bg-danger' : ($wp->finding->severity === 'repair_required' ? 'bg-warning text-dark' : 'bg-warning bg-opacity-50 text-dark') }} ms-1">dari checklist {{ $wp->finding->severity === 'critical' ? 'kritis' : 'perlu perbaikan' }}</span>
                                    @else
                                        <span class="badge bg-secondary ms-1">manual</span>
                                    @endif
                                    <small class="text-muted d-block">Std: {{ $wpTotals['standard_minutes'] }} mnt · Jasa Rp {{ number_format($wpTotals['labor_total'], 0, ',', '.') }} · Part Rp {{ number_format($wpTotals['part_total'], 0, ',', '.') }} · <strong>Rp {{ number_format($wpTotals['grand_total'], 0, ',', '.') }}</strong></small>
                                </span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <form method="POST" id="estimateForm"
                      action="{{ ($activeEstimate && $activeEstimate->isEditable()) ? route('estimates.update', $activeEstimate) : route('services.estimates.store', $service) }}">
                    @csrf
                    @if($activeEstimate && $activeEstimate->isEditable())
                    @method('PUT')
                    @endif

                    <div class="row g-2 mb-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Tanggal Estimasi</label>
                            <input type="date" name="estimate_date" class="form-control form-control-sm" value="{{ old('estimate_date', ($activeEstimate?->estimate_date ?? now())->format('Y-m-d')) }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Berlaku Sampai</label>
                            <input type="date" name="valid_until" class="form-control form-control-sm" value="{{ old('valid_until', ($activeEstimate?->valid_until ?? now()->addDays(7))->format('Y-m-d')) }}">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Diskon Dokumen</label>
                            <div class="input-group input-group-sm">
                                <input type="number" step="0.01" min="0" name="discount" class="form-control" value="{{ old('discount', $activeEstimate?->discount ?? 0) }}">
                                <select name="discount_type" class="form-select" style="max-width:90px">
                                    <option value="fixed" @selected(old('discount_type', 'fixed') === 'fixed')">Rp</option>
                                    <option value="percent" @selected(old('discount_type') === 'percent')>%</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label small">Catatan</label>
                            <input type="text" name="notes" class="form-control form-control-sm" value="{{ old('notes', $activeEstimate?->notes) }}" placeholder="Keluhan / catatan pemeriksaan">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <strong><i class="fas fa-list me-1"></i> Item Estimasi</strong>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addEstimateRow('part')"><i class="fas fa-cog me-1"></i> + Tambah Sparepart</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addEstimateRow('labor')"><i class="fas fa-tools me-1"></i> + Tambah Jasa</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addEstimateRow('other')"><i class="fas fa-plus me-1"></i> + Tambah Item Manual</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle" id="estimateItems">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:110px">Tipe</th>
                                    <th>Product / Description</th>
                                    <th style="min-width:80px">Qty</th>
                                    <th style="min-width:110px">Harga Satuan</th>
                                    <th style="min-width:100px">Diskon</th>
                                    <th style="min-width:80px">Pajak %</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldItems = old('items');
                                    if ($oldItems !== null) {
                                        $editItems = is_array($oldItems) ? $oldItems : $oldItems->toArray();
                                    } else {
                                        $editItems = $activeEstimate?->isEditable() ? $activeEstimate->items->map(fn ($i) => [
                                            'item_type' => $i->item_type, 'product_id' => $i->product_id, 'description' => $i->description,
                                            'quantity' => (string) $i->quantity, 'unit_price' => (string) $i->unit_price,
                                            'discount' => (string) $i->discount, 'discount_type' => $i->discount_type, 'tax_rate' => $i->tax_rate,
                                        ])->all() : [];
                                    }
                                @endphp
                                @foreach($editItems as $rowIndex => $row)
                                    @include('estimates.partials.item-row', ['row' => $row, 'products' => $products, 'rowIndex' => $rowIndex])
                                @endforeach
                                @if(count($editItems) === 0)
                                    @include('estimates.partials.item-row', ['row' => ['item_type' => 'part', 'product_id' => null, 'description' => '', 'quantity' => '1', 'unit_price' => '', 'discount' => '0', 'discount_type' => 'fixed', 'tax_rate' => null], 'products' => $products, 'rowIndex' => 0])
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end mb-3">
                        <div class="col-md-5 col-lg-4">
                            <table class="table table-sm mb-0" id="estimateTotals">
                                <tr><td class="text-muted">Subtotal</td><td class="text-end fw-semibold" id="live-subtotal">Rp 0</td></tr>
                                <tr><td class="text-muted">Diskon</td><td class="text-end" id="live-discount">Rp 0</td></tr>
                                <tr><td class="text-muted">Pajak</td><td class="text-end" id="live-tax">Rp 0</td></tr>
                                <tr class="table-dark"><td class="fw-bold">GRAND TOTAL <small class="text-warning">(preview — dihitung ulang server)</small></td><td class="text-end fw-bold" id="live-grand">Rp 0</td></tr>
                            </table>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>
                        {{ $activeEstimate?->isEditable() ? 'Simpan Perubahan' : 'Simpan Estimasi (Draft)' }}
                    </button>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-shield-alt me-1"></i>Estimasi tidak mengurangi stok dan tidak memicu pembukuan. Stok hanya berkurang saat parts benar-benar dipakai/konsumsi.
                    </small>
                </form>
            </div>
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

@once
@push('scripts')
<script>
(function () {
    'use strict';

    window.addEstimateRow = function (type) {
        const tbody = document.querySelector('#estimateItems tbody');
        const tpl = document.getElementById('estimate-row-template');
        const div = document.createElement('div');
        div.innerHTML = tpl.innerHTML.replace(/__ROW__/g, 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1e6));
        const tr = div.firstElementChild;
        tr.dataset.type = type;
        tr.querySelector('.est-type').value = type;
        tbody.appendChild(tr);
        renumberRows();
        updateLiveTotals();
    };

    window.removeEstimateRow = function (btn) {
        const tbody = document.querySelector('#estimateItems tbody');
        if (tbody.rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            btn.closest('tr').querySelector('.est-desc').value = '';
        }
        renumberRows();
        updateLiveTotals();
    };

    function renumberRows() {
        document.querySelectorAll('#estimateItems tbody tr').forEach(function (tr, i) {
            tr.querySelector('.row-idx').textContent = i + 1;
        });
    }

    function rowNumber(el) {
        const tr = el.closest('tr');
        const qty = parseFloat(tr.querySelector('.est-qty').value) || 0;
        const price = parseFloat(tr.querySelector('.est-price').value) || 0;
        const disc = parseFloat(tr.querySelector('.est-disc').value) || 0;
        const discType = tr.querySelector('.est-disc-type').value;
        const tax = parseFloat(tr.querySelector('.est-tax').value) || 0;

        const base = qty * price;
        const discVal = discType === 'percent' ? base * disc / 100 : Math.min(disc, base);
        const taxVal = (base - discVal) * tax / 100;
        return { base: base, disc: discVal, tax: taxVal, total: base - discVal + taxVal };
    }

    window.updateLiveTotals = function () {
        let subtotal = 0, discount = 0, tax = 0;
        document.querySelectorAll('#estimateItems tbody tr').forEach(function (tr) {
            const v = rowNumber(tr);
            subtotal += v.base; discount += v.disc; tax += v.tax;
            tr.querySelector('.est-line-total').textContent = fmt(v.total);
        });
        document.getElementById('live-subtotal').textContent = 'Rp ' + fmt(subtotal);
        document.getElementById('live-discount').textContent = '- Rp ' + fmt(discount);
        document.getElementById('live-tax').textContent = 'Rp ' + fmt(tax);
        document.getElementById('live-grand').textContent = 'Rp ' + fmt(subtotal - discount + tax);
    };

    function fmt(n) {
        return n.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    // Product select: prefill selling price (editable).
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('est-product')) return;
        const select = e.target;
        const tr = select.closest('tr');
        const opt = select.options[select.selectedIndex];
        const price = opt.getAttribute('data-price');
        if (price !== null && price !== '') {
            tr.querySelector('.est-price').value = price;
        }
        tr.querySelector('.est-product-id').value = select.value || '';
        const descInput = tr.querySelector('.est-desc');
        if (!descInput.value && opt.getAttribute('data-name')) {
            descInput.value = opt.getAttribute('data-name');
        }
        updateLiveTotals();
    });

    document.addEventListener('input', function (e) {
        if (e.target.closest('#estimateItems') && ['est-qty', 'est-price', 'est-disc', 'est-tax'].some(c => e.target.classList.contains(c))) {
            updateLiveTotals();
        }
        if (e.target.classList.contains('est-disc-type')) updateLiveTotals();
    });

    document.addEventListener('DOMContentLoaded', updateLiveTotals);
})();
</script>
@endpush
@endonce

{{-- Row template for dynamic rows --}}
<div id="estimate-row-template" class="d-none">
    @include('estimates.partials.item-row', ['row' => ['item_type' => 'part', 'product_id' => null, 'description' => '', 'quantity' => '1', 'unit_price' => '', 'discount' => '0', 'discount_type' => 'fixed', 'tax_rate' => null], 'products' => $products, 'isTemplate' => true])
</div>
