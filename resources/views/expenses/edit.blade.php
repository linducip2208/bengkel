@extends('layouts.app')
@section('title', 'Edit Pengeluaran')

@section('content')
<h4 class="mb-3">Edit Pengeluaran</h4>

<form method="POST" action="{{ route('expenses.update', $expense) }}">
    @csrf
    @method('PUT')
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Label *</label>
            <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label', $expense->label) }}" required>
            @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal *</label>
            <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required>
            @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Jumlah *</label>
            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $expense->amount) }}" min="1" step="1000" required>
            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Rekening Bank</label>
        <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror">
            <option value="">Pilih (opsional)</option>
            @foreach ($bankAccounts as $ba)
                <option value="{{ $ba->id }}" {{ old('bank_account_id', $expense->bank_account_id) == $ba->id ? 'selected' : '' }}>{{ $ba->bank_name }} — {{ $ba->account_number }}</option>
            @endforeach
        </select>
        @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description', $expense->description) }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
