@extends('layouts.app')
@section('title', 'Stock Opname')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0">Stock Opname</h4>
    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
</div>

<div class="alert alert-info no-print">
    <i class="bi bi-info-circle"></i> Masukkan stok fisik aktual untuk setiap produk. Produk dengan perbedaan stok akan ditandai.
</div>

<form action="{{ route('products.stock-opname') }}" method="POST" id="opname-form">
    @csrf

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="opname-table">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Nama Produk</th>
                        <th class="text-center" style="width:120px">Stok Sistem</th>
                        <th class="text-center" style="width:140px">Stok Fisik</th>
                        <th class="text-center" style="width:120px">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                    <tr class="opname-row">
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            <br><small class="text-muted">{{ $product->code }} &middot; {{ $product->productType?->name ?? $product->productType?->type ?? '-' }}</small>
                        </td>
                        <td class="text-center">
                            <span class="system-stock">{{ $product->current_stock }}</span>
                            <small class="text-muted d-block">{{ $product->unit?->name ?? '' }}</small>
                        </td>
                        <td class="text-center">
                            <input type="hidden" name="products[{{ $index }}][id]" value="{{ $product->id }}">
                            <input type="number"
                                   name="products[{{ $index }}][physical_stock]"
                                   class="form-control form-control-sm text-center physical-stock"
                                   value="{{ $product->current_stock }}"
                                   min="0"
                                   data-system="{{ $product->current_stock }}">
                        </td>
                        <td class="text-center">
                            <span class="difference badge bg-secondary">0</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Tidak ada produk.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-between no-print">
        <div>
            <span class="me-3">Produk dengan selisih: <strong id="diff-count" class="text-warning">0</strong></span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="printOpname()">
                <i class="bi bi-printer"></i> Print
            </button>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Simpan hasil stock opname? Stok akan disesuaikan untuk produk dengan selisih.')">
                <i class="bi bi-save"></i> Simpan Stock Opname
            </button>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    @media print {
        form { display: block !important; }

        body { background: #fff !important; }
        .sidebar, .topbar, .no-print { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
        .physical-stock { border: none !important; background: transparent !important; padding: 0 !important; text-align: center !important; -webkit-appearance: none; -moz-appearance: textfield; }
        .physical-stock::-webkit-inner-spin-button, .physical-stock::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .badge { border: 1px solid #000 !important; }
        .opname-print-header { display: block !important; }
    }
    .opname-print-header { display: none; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('.opname-row');

        function recalculate() {
            let diffCount = 0;
            rows.forEach(function (row) {
                const input = row.querySelector('.physical-stock');
                const diffBadge = row.querySelector('.difference');
                const systemStock = parseInt(input.dataset.system);
                const physicalStock = parseInt(input.value) || 0;
                const diff = physicalStock - systemStock;

                if (diff === 0) {
                    diffBadge.className = 'difference badge bg-secondary';
                    diffBadge.textContent = '0';
                } else if (diff > 0) {
                    diffBadge.className = 'difference badge bg-success';
                    diffBadge.textContent = '+' + diff;
                    diffCount++;
                } else {
                    diffBadge.className = 'difference badge bg-danger';
                    diffBadge.textContent = diff;
                    diffCount++;
                }
            });
            document.getElementById('diff-count').textContent = diffCount;
        }

        document.querySelectorAll('.physical-stock').forEach(function (input) {
            input.addEventListener('input', recalculate);
            input.addEventListener('change', recalculate);
        });
    });

    function printOpname() {
        var table = document.getElementById('opname-table');
        var header = table.querySelector('thead');
        if (!header.querySelector('.print-date')) {
            var printHeader = document.createElement('tr');
            printHeader.innerHTML = '<td colspan="5" class="opname-print-header text-center py-2"><h5>Laporan Stock Opname</h5><small class="text-muted">Tanggal: ' + new Date().toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'}) + ' &middot; Jam: ' + new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'}) + '</small></td>';
            header.insertBefore(printHeader, header.firstChild);
        }
        window.print();
    }
</script>
@endpush
