@extends('layouts.app')
@section('title', 'Buka Sesi Kasir')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Buka Sesi Kasir POS</h4>
    <a href="{{ route('pos.sessions') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Histori Sesi</a>
</div>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <p class="text-muted">Sebelum mulai jual, masukkan saldo kas awal di laci kasir.</p>
                <form action="{{ route('pos.open') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Saldo Awal (Opening Balance) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="opening_balance" class="form-control text-end @error('opening_balance') is-invalid @enderror" value="{{ old('opening_balance', 0) }}" min="0" step="1000" required autofocus>
                        </div>
                        @error('opening_balance') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Mis: shift pagi, kasir A">{{ old('notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-unlock me-1"></i>Buka Sesi & Mulai Jualan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
