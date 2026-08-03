@extends('layouts.app')
@section('title', 'Buat Klaim Garansi')
@section('content')
<div class="d-flex justify-content-between mb-4">
    <h4><i class="bi bi-shield-check me-2"></i>Buat Klaim Garansi Baru</h4>
    <a href="{{ route('warranty-claims.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>
<div class="card"><div class="card-body">
<form action="{{ route('warranty-claims.store') }}" method="POST">
    @csrf
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Pilih Item Pembelian Bergaransi <span class="text-danger">*</span></label>
            <select name="invoice_item_id" class="form-select @error('invoice_item_id') is-invalid @enderror" required>
                <option value="">— Pilih item —</option>
                @foreach($items as $i)
                <option value="{{ $i->id }}">
                    {{ $i->invoice->invoice_number }} — {{ $i->product->name ?? $i->description }} ({{ $i->invoice->customer->name ?? '?' }}) — garansi {{ $i->product->warranty_months ?? 0 }} bulan
                </option>
                @endforeach
            </select>
            @error('invoice_item_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6"><label class="form-label">Tanggal Klaim <span class="text-danger">*</span></label><input type="date" name="claim_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="col-12"><label class="form-label">Keluhan Customer <span class="text-danger">*</span></label><textarea name="complaint" class="form-control" rows="4" placeholder="Detail kerusakan / masalah parts..." required></textarea></div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Simpan Klaim</button>
</form>
</div></div>
@endsection
