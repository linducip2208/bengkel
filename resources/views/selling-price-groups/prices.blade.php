@extends('layouts.app')
@section('title', 'Atur Harga Produk')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="fas fa-dollar-sign me-2"></i>Harga Produk — {{ $sellingPriceGroup->name }}
    </h4>
    <a href="{{ route('selling-price-groups.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<form method="POST" action="{{ route('selling-price-groups.prices.store', $sellingPriceGroup) }}">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Kode</th>
                            <th class="text-end">Harga Default</th>
                            <th class="text-end" style="width: 220px;">Harga Grup</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                        <tr>
                            <td>{{ $p->name }}</td>
                            <td><code>{{ $p->code }}</code></td>
                            <td class="text-end">@money($p->price)</td>
                            <td class="text-end">
                                <input type="number" step="0.01" min="0" name="prices[{{ $p->id }}]"
                                       class="form-control form-control-sm text-end"
                                       value="{{ $existing->has($p->id) ? $existing->get($p->id) : '' }}"
                                       placeholder="Kosongkan = default">
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada produk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan Harga</button>
        </div>
    </div>
</form>
@endsection
