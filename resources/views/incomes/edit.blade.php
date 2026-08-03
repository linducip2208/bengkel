@extends('layouts.app')
@section('title', 'Edit Pemasukan')

@section('content')
<h4 class="mb-3">Edit Pemasukan</h4>

<form method="POST" action="{{ route('incomes.update', $income) }}">
    @csrf
    @method('PUT')
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Label *</label>
            <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label', $income->label) }}" required>
            @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal *</label>
            <input type="date" name="income_date" class="form-control @error('income_date') is-invalid @enderror" value="{{ old('income_date', $income->income_date->format('Y-m-d')) }}" required>
            @error('income_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Jumlah *</label>
            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $income->amount) }}" min="1" step="1000" required>
            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Pelanggan</label>
            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror">
                <option value="">Pilih (opsional)</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id', $income->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Metode Pembayaran</label>
            <select name="payment_method_id" class="form-select @error('payment_method_id') is-invalid @enderror">
                <option value="">Pilih (opsional)</option>
                @foreach ($paymentMethods as $pm)
                    <option value="{{ $pm->id }}" {{ old('payment_method_id', $income->payment_method_id) == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                @endforeach
            </select>
            @error('payment_method_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">No. Invoice</label>
            <input type="text" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number', $income->invoice_number) }}">
            @error('invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $income->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
        <a href="{{ route('incomes.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
