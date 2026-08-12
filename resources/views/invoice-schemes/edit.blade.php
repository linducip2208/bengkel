@extends('layouts.app')
@section('title', 'Edit Numbering')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-hashtag me-2"></i>Edit Numbering</h4>
    <a href="{{ route('invoice-schemes.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('invoice-schemes.update', $invoiceScheme) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Skema <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $invoiceScheme->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Prefix <span class="text-danger">*</span></label>
                <input type="text" name="prefix" class="form-control @error('prefix') is-invalid @enderror" value="{{ old('prefix', $invoiceScheme->prefix) }}" required>
                @error('prefix') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Format <span class="text-danger">*</span></label>
                <input type="text" name="format" class="form-control @error('format') is-invalid @enderror" value="{{ old('format', $invoiceScheme->format) }}" required>
                <div class="form-text">Gunakan placeholder: {prefix}, {year}, {month}, {seq}.</div>
                @error('format') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Cabang</label>
                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                    <option value="">Tanpa Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $invoiceScheme->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Start Number</label>
                <input type="number" name="start_number" class="form-control @error('start_number') is-invalid @enderror" value="{{ old('start_number', $invoiceScheme->start_number) }}" min="1">
                @error('start_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label">Next Number</label>
                <input type="number" name="next_number" class="form-control @error('next_number') is-invalid @enderror" value="{{ old('next_number', $invoiceScheme->next_number) }}" min="1">
                @error('next_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" {{ old('is_default', $invoiceScheme->is_default) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_default">Jadikan default</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $invoiceScheme->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Aktif</label>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
            <a href="{{ route('invoice-schemes.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
