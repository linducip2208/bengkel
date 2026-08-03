@extends('layouts.app')
@section('title', 'Catat Penjualan')

@section('content')
<h4 class="mb-3">Catat Penjualan Kendaraan</h4>

<form method="POST" action="{{ route('sales.store') }}">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Pelanggan *</label>
            <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                <option value="">Pilih Pelanggan</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label class="form-label">Kendaraan *</label>
            <select name="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror" required>
                <option value="">Pilih Kendaraan</option>
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>
                        {{ $v->number_plate }} - {{ $v->vehicleBrand?->name }} {{ $v->model_name }}
                    </option>
                @endforeach
            </select>
            @error('vehicle_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Tanggal *</label>
            <input type="date" name="sale_date" class="form-control @error('sale_date') is-invalid @enderror" value="{{ old('sale_date', date('Y-m-d')) }}" required>
            @error('sale_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select @error('status') is-invalid @enderror">
                <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Selesai</option>
                <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Batal</option>
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Harga Jual *</label>
            <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" min="1" step="100000" required>
            @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Uang Muka (DP)</label>
            <input type="number" name="down_payment" class="form-control @error('down_payment') is-invalid @enderror" value="{{ old('down_payment', 0) }}" min="0" step="100000">
            @error('down_payment') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Deskripsi</label>
        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="2">{{ old('description') }}</textarea>
        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
