@extends('layouts.app')
@section('title', 'Detail Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Produk</h4>
    <div>
        <a href="{{ route('products.barcode', $product) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="bi bi-upc-scan"></i> Barcode
        </a>
        <a href="{{ route('products.stock-adjust', $product) }}" class="btn btn-info btn-sm">
            <i class="bi bi-box-arrow-in-down"></i> Stok Adjust
        </a>
        <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi Produk</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="180" class="text-muted">No. Produk</td>
                        <td><strong>{{ $product->product_no }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kode</td>
                        <td>{{ $product->code }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Nama</td>
                        <td>{{ $product->name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tipe</td>
                        <td>{{ $product->productType?->name ?? $product->productType?->type ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Satuan</td>
                        <td>{{ $product->unit?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Supplier</td>
                        <td>{{ $product->supplier?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga Jual</td>
                        <td><strong>@money($product->price)</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Harga Beli</td>
                        <td>{{ $product->cost_price ? \App\Models\Currency::format($product->cost_price) : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Garansi</td>
                        <td>{{ $product->warranty ?: '-' }}</td>
                    </tr>
                    @if($product->description)
                    <tr>
                        <td class="text-muted">Deskripsi</td>
                        <td>{{ $product->description }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Informasi Stok</h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h2 class="mb-0
                        @if($product->current_stock <= 0) text-danger
                        @elseif($product->minimum_stock && $product->current_stock <= $product->minimum_stock) text-warning
                        @else text-success
                        @endif">
                        {{ $product->current_stock }}
                    </h2>
                    <small class="text-muted">Stok Saat Ini</small>
                    @if($product->reserved_quantity > 0)
                        <br>
                        <span class="badge bg-warning text-dark mt-1">Reservasi: {{ $product->reserved_quantity }}</span>
                        <br><small class="text-muted">Tersedia: {{ $product->available_stock }}</small>
                    @endif
                </div>
                <hr>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Stok Minimum</td>
                        <td class="text-end">{{ $product->minimum_stock ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Lokasi Rak</td>
                        <td class="text-end">{{ $product->rack_location ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Penyesuaian Terakhir</td>
                        <td class="text-end">
                            @php
                                $lastAdjust = $product->stockHistories->first();
                            @endphp
                            {{ $lastAdjust ? $lastAdjust->created_at->format('d/m/Y H:i') : '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Variasi Produk</h6>
        <a href="{{ route('products.variations.index', $product) }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-sitemap me-1"></i> Kelola Variasi
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama</th>
                    <th>SKU</th>
                    <th class="text-end">Harga</th>
                    <th class="text-center">Stok</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($product->variations as $v)
                <tr>
                    <td>{{ $v->name }}</td>
                    <td>{{ $v->sku ?: '-' }}</td>
                    <td class="text-end">@money($v->price ?? $product->price)</td>
                    <td class="text-center">{{ $v->stock }}</td>
                    <td>
                        @if($v->is_active)
                        <span class="badge bg-success">Aktif</span>
                        @else
                        <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">Belum ada variasi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h6 class="card-title mb-0">Riwayat Pembelian</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>No. PO</th>
                    <th>Supplier</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-end">Harga Satuan</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseHistory as $item)
                <tr>
                    <td>{{ $item->purchase?->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                    <td>
                        <a href="{{ route('purchases.show', $item->purchase) }}" class="text-decoration-none">
                            {{ $item->purchase?->purchase_no ?? '-' }}
                        </a>
                    </td>
                    <td>{{ $item->purchase?->supplier?->name ?? '-' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-end">@money($item->unit_price)</td>
                    <td class="text-end">@money($item->total_price)</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat pembelian.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($purchaseHistory->hasPages())
    <div class="card-footer">
        {{ $purchaseHistory->links() }}
    </div>
    @endif
</div>

<div class="card mt-3">
    <div class="card-header">
        <h6 class="card-title mb-0">Riwayat Stok</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th class="text-center">Perubahan</th>
                    <th class="text-center">Stok Lama</th>
                    <th class="text-center">Stok Baru</th>
                    <th>Alasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($product->stockHistories as $history)
                <tr>
                    <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                            $typeLabels = [
                                'initial' => ['bg-info', 'Awal'],
                                'purchase' => ['bg-success', 'Pembelian'],
                                'usage' => ['bg-primary', 'Pemakaian'],
                                'adjustment_add' => ['bg-light text-dark', 'Tambah'],
                                'adjustment_reduce' => ['bg-light text-dark', 'Kurangi'],
                                'opname' => ['bg-warning text-dark', 'Opname'],
                            ];
                            $badge = $typeLabels[$history->type] ?? ['bg-secondary', $history->type];
                        @endphp
                        <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="{{ $history->quantity_change > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $history->quantity_change > 0 ? '+' : '' }}{{ $history->quantity_change }}
                        </span>
                    </td>
                    <td class="text-center">{{ $history->previous_stock }}</td>
                    <td class="text-center">{{ $history->new_stock }}</td>
                    <td><small>{{ $history->reason ?: '-' }}</small></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat stok.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
