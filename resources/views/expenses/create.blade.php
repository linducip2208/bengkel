@extends('layouts.app')
@section('title', 'Catat Pengeluaran')

@section('content')
<h4 class="mb-3">Catat Pengeluaran Baru</h4>

<form method="POST" action="{{ route('expenses.store') }}">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Label *</label>
            <input type="text" name="label" class="form-control @error('label') is-invalid @enderror" value="{{ old('label') }}" required placeholder="contoh: Beli Sparepart, Bayar Listrik">
            @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Tanggal *</label>
            <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', date('Y-m-d')) }}" required>
            @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Jumlah *</label>
            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" min="1" step="1000" required>
            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
