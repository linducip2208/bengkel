@extends('layouts.app')
@section('title', 'Detail Klaim Garansi')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-shield-check me-2"></i>Detail Klaim #{{ $warrantyClaim->id }}</h4>
    <div>
        <a href="{{ route('warranty-claims.edit', $warrantyClaim) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="{{ route('warranty-claims.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
</div>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Informasi Klaim</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:130px;">Status</td><td>
                        @php
                            $badges = ['submitted'=>'warning','approved'=>'primary','rejected'=>'danger','resolved'=>'success'];
                        @endphp
                        <span class="badge bg-{{ $badges[$warrantyClaim->status] ?? 'secondary' }}">{{ ucfirst($warrantyClaim->status) }}</span>
                    </td></tr>
                    <tr><td>Tanggal Klaim</td><td>{{ $warrantyClaim->claim_date->format('d M Y') }}</td></tr>
                    <tr><td>Customer</td><td>{{ $warrantyClaim->customer?->name ?? '-' }}</td></tr>
                    <tr><td>Invoice</td><td><code>{{ $warrantyClaim->invoiceItem?->invoice?->invoice_number }}</code></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Produk</strong></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width:130px;">Produk</td><td>{{ $warrantyClaim->invoiceItem?->product?->name ?? $warrantyClaim->invoiceItem?->description ?? '-' }}</td></tr>
                    <tr><td>Garansi</td><td>{{ $warrantyClaim->invoiceItem?->product?->warranty_months ?? 0 }} bulan</td></tr>
                    <tr><td>Harga</td><td>@money($warrantyClaim->invoiceItem?->unit_price ?? 0)</td></tr>
                    <tr><td>Qty</td><td>{{ $warrantyClaim->invoiceItem?->quantity ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Keluhan Customer</strong></div>
            <div class="card-body">
                <p class="mb-0">{{ $warrantyClaim->complaint }}</p>
            </div>
        </div>
    </div>
    @if($warrantyClaim->resolution)
    <div class="col-12">
        <div class="card">
            <div class="card-header"><strong>Resolusi</strong></div>
            <div class="card-body">
                <p class="mb-0">{{ $warrantyClaim->resolution }}</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
