@extends('layouts.app')
@section('title', 'Catat Pembayaran')

@section('content')
<h4 class="mb-3">Catat Pembayaran</h4>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-6">
                <small class="text-muted">Invoice</small>
                <div><strong>{{ $invoice->invoice_number }}</strong></div>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted">Pelanggan</small>
                <div><strong>{{ $invoice->customer->name ?? '-' }}</strong></div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-4">
                <small class="text-muted">Total Invoice</small>
                <div><strong>@money($invoice->grand_total)</strong></div>
            </div>
            <div class="col-4">
                <small class="text-muted">Sudah Dibayar</small>
                <div>@money($invoice->paymentRecords->sum('amount'))</div>
            </div>
            <div class="col-4">
                <small class="text-muted">Sisa</small>
                <div><strong class="text-danger">@money($remaining)</strong></div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('payments.store', $invoice) }}">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Jumlah Pembayaran *</label>
            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $remaining) }}" min="1" step="1" required>
            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Metode Pembayaran *</label>
            <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror" required>
                <option value="">Pilih Metode</option>
                @foreach ($paymentMethods as $pm)
                    <option value="{{ $pm->id }}" {{ old('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                @endforeach
            </select>
            @error('payment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Tanggal Pembayaran *</label>
            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Nomor Referensi</label>
            <input type="text" name="reference_number" class="form-control @error('reference_number') is-invalid @enderror" value="{{ old('reference_number') }}" placeholder="No. transfer / bukti">
            @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Catatan</label>
        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes') }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Catat Pembayaran</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
