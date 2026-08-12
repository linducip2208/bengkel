@extends('layouts.app')
@section('title', 'Retur Pembelian')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-undo-alt me-2"></i>Retur Pembelian</h4>
    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Kembali ke Pembelian
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('purchases.return.store', $purchase) }}" method="POST">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <table class="table table-sm">
                        <tr><th class="w-25">No. PO</th><td><strong>{{ $purchase->purchase_no }}</strong></td></tr>
                        <tr><th>Supplier</th><td>{{ $purchase->supplier->name ?? '-' }}</td></tr>
                        <tr><th>Tanggal PO</th><td>{{ $purchase->purchase_date->format('d M Y') }}</td></tr>
                        <tr><th>Status</th><td>{!! $purchase->status_badge !!}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="return_reason" class="form-label">Alasan Retur <span class="text-danger">*</span></label>
                        <textarea name="return_reason" id="return_reason" rows="3" class="form-control" required
                            placeholder="Jelaskan alasan retur pembelian ini...">{{ old('return_reason') }}</textarea>
                        @error('return_reason')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <h6 class="mb-3"><i class="fas fa-boxes me-1"></i>Item yang diretur</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Tipe</th>
                            <th class="text-center">Qty PO</th>
                            <th class="text-center">Stok Saat Ini</th>
                            <th class="text-center">Qty Retur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchase->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name ?? 'Produk #' . $item->product_id }}</strong>
                                <br><small class="text-muted">{{ $item->product->code ?? '-' }}</small>
                            </td>
                            <td>{{ $item->product->productType->name ?? '-' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-center">{{ $item->product->current_stock ?? 0 }}</td>
                            <td class="text-center" style="width: 140px;">
                                <input type="hidden" name="return_items[{{ $loop->index }}][product_id]" value="{{ $item->product_id }}">
                                <input type="number" name="return_items[{{ $loop->index }}][quantity]"
                                    class="form-control form-control-sm text-center"
                                    min="0" max="{{ max($item->quantity, $item->product->current_stock ?? 0) }}"
                                    value="0"
                                    style="width: 100px; display: inline-block;">
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Tidak ada item dalam purchase order ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @error('return_items')
                <div class="text-danger small mb-3">{{ $message }}</div>
            @enderror

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('purchases.return.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Batal
                </a>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Proses retur pembelian ini? Stok akan dikurangi.')">
                    <i class="fas fa-undo-alt me-1"></i>Proses Retur
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
