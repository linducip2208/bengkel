@extends('layouts.app')
@section('title', 'Rekonsiliasi Bank Baru')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-balance-scale me-2"></i>Rekonsiliasi Bank Baru</h4>
    <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('bank-reconciliations.store') }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Rekening Bank <span class="text-danger">*</span></label>
                <select name="bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required>
                    <option value="">Pilih Rekening</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('bank_account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }} — {{ $account->bank_name }} ({{ $account->account_number }})</option>
                    @endforeach
                </select>
                @error('bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Saldo Rekening Koran <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" name="statement_balance" class="form-control @error('statement_balance') is-invalid @enderror" value="{{ old('statement_balance') }}" required>
                @error('statement_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Simpan</button>
            <a href="{{ route('bank-reconciliations.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
