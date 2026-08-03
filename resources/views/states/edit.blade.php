@extends('layouts.app')
@section('title', 'Edit Provinsi')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-map me-2"></i>Edit Provinsi</h4>
    <a href="{{ route('states.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Kembali</a>
</div>
<div class="card"><div class="card-body">
    <form action="{{ route('states.update', $state) }}" method="POST">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Negara <span class="text-danger">*</span></label>
                <select name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}" {{ old('country_id', $state->country_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('country_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-7">
                <label class="form-label">Nama Provinsi <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $state->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Perbarui</button>
        </div>
    </form>
</div></div>
@endsection
