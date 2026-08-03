@extends('layouts.app')

@section('title', 'Stock Report - Bengkel Paten')

@section('content')
<h4 class="mb-3">Stock Report</h4>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories ?? [] as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('reports.stock') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body text-center">
                <h4>{{ $report['total_products'] ?? 0 }}</h4>
                <p class="text-muted">Total Products</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h4>@money($report['total_value'] ?? 0)</h4>
                <p class="text-muted">Total Inventory Value</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body text-center">
                <h4>{{ ($report['low_stock'] ?? collect())->count() }}</h4>
                <p class="text-muted">Low Stock Items</p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mb-3 gap-2">
    <a href="{{ route('reports.export-pdf', ['type' => 'stock'] + request()->all()) }}" class="btn btn-danger btn-sm"><i class="bi bi-file-pdf"></i> Export PDF</a>
    <a href="{{ route('reports.export-excel', ['type' => 'stock'] + request()->all()) }}" class="btn btn-success btn-sm"><i class="bi bi-file-excel"></i> Export Excel</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Current Stock</th>
                    <th>Unit Cost</th>
                    <th>Total Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['products'] ?? [] as $product)
                <tr class="{{ $product->current_stock <= 5 ? 'table-danger' : '' }}">
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->productType->name ?? '-' }}</td>
                    <td>{{ $product->current_stock }} {{ $product->productUnit->name ?? '' }}</td>
                    <td>@money($product->cost_price ?? $product->price ?? 0)</td>
                    <td>@money($product->total_value ?? 0)</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
