@php
    // Snapshot after issue — approved estimates never re-render from mutable master data.
    $issued = $estimate->isIssued();
    $items = $estimate->items;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Estimasi {{ $estimate->estimate_number }}</title>
    <style>
        @page { margin: 46px 40px 60px 40px; }
        * { box-sizing: border-box; }
        body { font-family: helvetica, sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; }
        .doc-header { width: 100%; border-collapse: collapse; border-bottom: 2px solid #1a1a1a; margin-bottom: 10px; }
        .doc-header td { vertical-align: top; padding: 2px 0; }
        .company-logo img { max-width: 62px; max-height: 62px; }
        .company-info { padding-left: 8px !important; }
        .company-info h2 { margin: 0 0 3px 0; font-size: 15px; }
        .company-info p { margin: 0; font-size: 9.5px; color: #333; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 22px; letter-spacing: 3px; }
        .doc-title .doc-number { font-size: 12px; font-weight: bold; margin-top: 4px; }
        .doc-title .doc-version { font-size: 9px; color: #555; margin-top: 1px; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { border: 1px solid #999; padding: 5px 7px; vertical-align: top; font-size: 10.5px; }
        .meta .label { color: #444; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .meta .right { width: 44%; }

        .vehicle { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .vehicle td { border: 1px solid #999; padding: 4px 7px; font-size: 10px; text-align: center; }
        .vehicle td.label { background: #f0f0f0; font-weight: bold; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.5px; }

        table.items { width: 100%; border-collapse: collapse; }
        table.items thead { display: table-header-group; }
        table.items th { background: #1a1a1a; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 6px 6px; border: 1px solid #1a1a1a; }
        table.items td { border: 1px solid #bbb; padding: 4.5px 6px; font-size: 10.5px; vertical-align: top; }
        table.items tr { page-break-inside: avoid; }
        .num { text-align: right; white-space: nowrap; }
        .ctr { text-align: center; }
        .item-type { font-size: 7.5px; text-transform: uppercase; color: #666; }
        .src-badge { font-size: 8px; padding: 1px 5px; border-radius: 3px; margin-left: 4px; }
        .src-critical { background: #b30000; color: #fff; }
        .src-repair { background: #e8a33d; color: #111; }
        .src-attention { background: #f5e08a; color: #111; }
        .src-manual { background: #888; color: #fff; }

        table.totals { width: 62%; border-collapse: collapse; margin-top: 10px; margin-left: auto; page-break-inside: avoid; }
        table.totals td { padding: 4px 7px; font-size: 11px; }
        table.totals td.lbl { text-align: right; color: #333; }
        table.totals td.val { text-align: right; width: 40%; white-space: nowrap; }
        table.totals tr.grand td { border-top: 2px solid #1a1a1a; font-weight: bold; font-size: 13px; padding-top: 6px; }

        .notes { margin-top: 12px; page-break-inside: avoid; }
        .notes .notes-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #1a1a1a; margin-bottom: 3px; padding-bottom: 2px; }
        .notes p { margin: 0; font-size: 10px; }

        .doc-footer { position: fixed; bottom: -34px; left: -40px; right: -40px; font-size: 8.5px; color: #555; text-align: center; }
        .doc-footer .terms { border-top: 1px solid #999; margin: 0 40px; padding-top: 4px; }
        .thanks { margin-top: 16px; font-style: italic; font-size: 10.5px; }
    </style>
</head>
<body>
    <table class="doc-header">
        <tr>
            <td class="company-logo">
                @if(!empty($company['logo']) && file_exists(public_path('storage/'.$company['logo'])))
                    <img src="{{ public_path('storage/'.$company['logo']) }}">
                @endif
            </td>
            <td class="company-info">
                <h2>{{ $company['name'] ?? config('app.name') }}</h2>
                <p>{{ $company['address'] }}</p>
                <p>Telp: {{ $company['phone'] ?: '-' }} &nbsp;|&nbsp; Email: {{ $company['email'] ?: '-' }}</p>
                @if(!empty($company['tax_id']))
                <p>NPWP: {{ $company['tax_id'] }}</p>
                @endif
            </td>
            <td class="doc-title">
                <h1>ESTIMASI</h1>
                <div class="doc-number">{{ $estimate->estimate_number }}</div>
                <div class="doc-version">Versi {{ $estimate->version }} · {{ $estimate->statusLabel() }}</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td>
                <span class="label">Kepada / Pelanggan</span>
                <strong>{{ $customer['name'] ?? '-' }}</strong><br>
                Telp: {{ $customer['phone'] ?? '-' }}<br>
                @if(!empty($customer['address'])){{ $customer['address'] }}@endif
            </td>
            <td class="right">
                <span class="label">Tgl Estimasi</span> {{ $estimate->estimate_date?->format('d M Y') ?? '-' }}
                <br><span class="label" style="margin-top:4px">Berlaku Sampai</span> {{ $estimate->valid_until?->format('d M Y') ?? '-' }}
                <br><span class="label" style="margin-top:4px">Tipe</span> Service
            </td>
        </tr>
    </table>

    <table class="vehicle">
        <tr>
            <td class="label" style="width:20%">Jenis Kendaraan</td>
            <td class="label" style="width:18%">No. Plat</td>
            <td class="label" style="width:10%">Tahun</td>
            <td class="label" style="width:12%">KM</td>
            <td class="label" style="width:20%">No. Service</td>
        </tr>
        <tr>
            <td>{{ trim(($vehicle['brand'] ?? '').' '.($vehicle['model'] ?? '')) ?: ($vehicle['type'] ?? '-') }}</td>
            <td>{{ $vehicle['number_plate'] ?? '-' }}</td>
            <td>{{ $vehicle['year'] ?? '-' }}</td>
            <td>{{ $vehicle['odometer'] !== null ? number_format((float) $vehicle['odometer'], 0, ',', '.') : '-' }}</td>
            <td>{{ $service['number'] ?? '-' }}</td>
        </tr>
    </table>

    <table class="items">
        @php $renderedGroupIds = []; @endphp
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th>Deskripsi</th>
                <th style="width:10%" class="ctr">Qty</th>
                <th style="width:16%" class="num">Harga Satuan</th>
                <th style="width:18%" class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            @php
                $group = $estimate->groups->firstWhere('id', $item->estimate_group_id);
                $renderGroupHeader = $group !== null && ! in_array($group->id, $renderedGroupIds);
                if ($renderGroupHeader) {
                    $renderedGroupIds[] = $group->id;
                }
            @endphp
            @if($renderGroupHeader)
            <tr>
                <td colspan="5" style="background:#efefef;">
                    <strong>{{ $group->title }}</strong>
                    @if($group->severity_snapshot === 'critical')
                        <span class="src-badge src-critical">dari checklist kritis</span>
                    @elseif($group->severity_snapshot === 'repair_required')
                        <span class="src-badge src-repair">dari checklist perlu perbaikan</span>
                    @elseif($group->severity_snapshot === 'attention')
                        <span class="src-badge src-attention">dari checklist perlu perhatian</span>
                    @else
                        <span class="src-badge src-manual">manual</span>
                    @endif
                    @if($group->finding)
                        <div style="color:#555; margin-top:3px;">Temuan: {{ $group->finding->finding_number }} · {{ $group->finding->title }}</div>
                        @if($group->finding->measurement_value !== null)<div style="color:#555; font-size:9px;">Hasil: {{ $group->finding->measurement_value }} {{ $group->finding->measurement_unit }}</div>@endif
                        @if($group->finding->recommendation)<div style="color:#555; font-size:9px;">Rekomendasi: {{ $group->finding->recommendation }}</div>@endif
                    @endif
                    @if($group->standard_minutes > 0)
                        <div style="color:#555; font-size:9px;">Standar waktu: {{ $group->standard_minutes }} menit</div>
                    @endif
                    <div style="color:#555; font-size:9px;">Status persetujuan: {{ \App\Models\ServiceEstimateGroup::DECISION_LABELS[$group->customer_decision] ?? $group->customer_decision }}</div>
                </td>
            </tr>
            @endif
            <tr>
                <td class="ctr">{{ $loop->iteration }}</td>
                <td>
                    {{ $item->description }}
                    @if($item->item_type === \App\Models\ServiceEstimateItem::TYPE_LABOR)
                        <span class="item-type">[Jasa]</span>
                    @elseif($item->item_type === \App\Models\ServiceEstimateItem::TYPE_OTHER)
                        <span class="item-type">[Lainnya]</span>
                    @endif
                </td>
                <td class="ctr">{{ rtrim(rtrim(number_format((float) $item->quantity, 2, ',', '.'), '0'), ',') }}</td>
                <td class="num">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                <td class="num">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="ctr" style="padding:14px">Tidak ada item.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="lbl">Subtotal</td><td class="val">Rp {{ number_format((float) $estimate->subtotal, 0, ',', '.') }}</td></tr>
        @if((float) $estimate->discount > 0)
        <tr><td class="lbl">Diskon</td><td class="val">- Rp {{ number_format((float) $estimate->discount, 0, ',', '.') }}</td></tr>
        @endif
        @if((float) $estimate->tax_amount > 0)
        <tr><td class="lbl">Pajak</td><td class="val">Rp {{ number_format((float) $estimate->tax_amount, 0, ',', '.') }}</td></tr>
        @endif
        <tr class="grand"><td class="lbl">GRAND TOTAL</td><td class="val">Rp {{ number_format((float) $estimate->grand_total, 0, ',', '.') }}</td></tr>
    </table>

    <div class="notes">
        <div class="notes-title">Catatan</div>
        <p>{{ $estimate->notes ?: ($service['title'] ?? '') }}</p>
        @if(!empty($service['description']))
        <p style="margin-top:3px">{{ $service['description'] }}</p>
        @endif
    </div>

    <p class="thanks">Terima kasih atas kepercayaan Anda.</p>

    <div class="doc-footer">
        <div class="terms">
            {{ $estimate->terms }}
            <br>Dokumen ini adalah estimasi — bukan invoice dan bukan bukti pembayaran.
        </div>
    </div>
</body>
</html>
