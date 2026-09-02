@php
    // Shared visual reference of the estimate document (used by print view).
    $issued = $estimate->isIssued();
@endphp
<div class="doc-sheet">
    <table style="width:100%" class="table table-borderless mb-2">
        <tr>
            <td style="width:60%">
                <h5 class="mb-0">{{ $company['name'] ?? config('app.name') }}</h5>
                <small class="text-muted d-block">{{ $company['address'] }}</small>
                <small class="text-muted d-block">Telp: {{ $company['phone'] }} | Email: {{ $company['email'] }}@if(!empty($company['tax_id'])) | NPWP: {{ $company['tax_id'] }}@endif</small>
            </td>
            <td class="text-end">
                <h3 class="mb-0">ESTIMASI</h3>
                <strong>{{ $estimate->estimate_number }}</strong>
                <div><span class="badge bg-{{ $estimate->statusColor() }}">v{{ $estimate->version }} — {{ $estimate->statusLabel() }}</span></div>
            </td>
        </tr>
    </table>
    <hr>
    <div class="row mb-2">
        <div class="col-7">
            <small class="text-muted text-uppercase">Kepada / Pelanggan</small>
            <div><strong>{{ $customer['name'] ?? '-' }}</strong></div>
            <small>Telp: {{ $customer['phone'] ?? '-' }}</small><br>
            <small>{{ $customer['address'] ?? '' }}</small>
        </div>
        <div class="col-5 text-end">
            <small class="text-muted text-uppercase">Tgl Estimasi</small>
            <div>{{ $estimate->estimate_date?->format('d M Y') ?? '-' }}</div>
            <small class="text-muted text-uppercase">Berlaku Sampai</small>
            <div>{{ $estimate->valid_until?->format('d M Y') ?? '-' }}</div>
            <small class="text-muted text-uppercase">Tipe</small>
            <div>Service</div>
        </div>
    </div>
    <table class="table table-sm table-bordered text-center mb-2">
        <tr>
            <td><small class="text-muted d-block">Jenis Kendaraan</small>{{ trim(($vehicle['brand'] ?? '').' '.($vehicle['model'] ?? '')) ?: ($vehicle['type'] ?? '-') }}</td>
            <td><small class="text-muted d-block">No. Plat</small>{{ $vehicle['number_plate'] ?? '-' }}</td>
            <td><small class="text-muted d-block">Tahun</small>{{ $vehicle['year'] ?? '-' }}</td>
            <td><small class="text-muted d-block">KM</small>{{ $vehicle['odometer'] !== null ? number_format((float) $vehicle['odometer'], 0, ',', '.') : '-' }}</td>
            <td><small class="text-muted d-block">No. Service</small>{{ $service['number'] ?? '-' }}</td>
        </tr>
    </table>
    <table class="table table-sm table-bordered align-middle">
        @php $renderedGroupIds = []; @endphp
        <thead><tr><th>#</th><th>Deskripsi</th><th class="text-center">Qty</th><th class="text-end">Harga Satuan</th><th class="text-end">Total</th></tr></thead>
        <tbody>
            @forelse($estimate->items as $item)
            @php
                $group = $estimate->groups->firstWhere('id', $item->estimate_group_id);
                $renderGroupHeader = $group !== null && ! in_array($group->id, $renderedGroupIds);
                if ($renderGroupHeader) {
                    $renderedGroupIds[] = $group->id;
                }
            @endphp
            @if($renderGroupHeader)
                <tr class="table-light">
                    <td colspan="5">
                        <strong>{{ $group->title }}</strong>
                        @if($group->severity_snapshot === 'critical')
                            <span class="badge bg-danger">dari checklist kritis</span>
                        @elseif($group->severity_snapshot === 'repair_required')
                            <span class="badge bg-warning text-dark">dari checklist perlu perbaikan</span>
                        @elseif($group->severity_snapshot === 'attention')
                            <span class="badge bg-warning bg-opacity-50 text-dark">dari checklist perlu perhatian</span>
                        @else
                            <span class="badge bg-secondary">manual</span>
                        @endif
                        @if($group->service_finding_id && $group->finding)
                            <small class="text-muted ms-1">Sumber: {{ $group->finding->finding_number }}</small>
                        @endif
                        @if($group->standard_minutes > 0)
                            <small class="text-muted d-block">Standar waktu: {{ $group->standard_minutes }} menit</small>
                        @endif
                    </td>
                </tr>
            @endif
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td class="text-end">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada item.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end">Rp {{ number_format((float) $estimate->subtotal, 0, ',', '.') }}</td></tr>
            @if((float) $estimate->discount > 0)
            <tr><td colspan="4" class="text-end">Diskon</td><td class="text-end">- Rp {{ number_format((float) $estimate->discount, 0, ',', '.') }}</td></tr>
            @endif
            @if((float) $estimate->tax_amount > 0)
            <tr><td colspan="4" class="text-end">Pajak</td><td class="text-end">Rp {{ number_format((float) $estimate->tax_amount, 0, ',', '.') }}</td></tr>
            @endif
            <tr class="table-dark"><td colspan="4" class="text-end fw-bold">GRAND TOTAL</td><td class="text-end fw-bold">Rp {{ number_format((float) $estimate->grand_total, 0, ',', '.') }}</td></tr>
        </tfoot>
    </table>
    @if($estimate->notes)
    <div class="mb-2"><small class="text-muted text-uppercase fw-bold">Catatan</small><p class="mb-0">{{ $estimate->notes }}</p></div>
    @endif
    <hr>
    <small class="text-muted">Dokumen ini adalah estimasi — bukan invoice dan bukan bukti pembayaran.</small>
</div>
