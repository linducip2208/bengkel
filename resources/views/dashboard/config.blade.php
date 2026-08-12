@extends('layouts.app')

@section('title', 'Atur Widget Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Atur Widget Dashboard</h4>
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form action="{{ route('dashboard.config.save') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pilih widget yang tampil di dashboard</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Centang widget yang ingin ditampilkan di dashboard Anda.
                        Jika tidak ada yang dicentang, seluruh section akan disembunyikan.
                    </p>

                    @foreach($widgets as $key => $label)
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="widgets[]" value="{{ $key }}"
                            id="widget-{{ $key }}" {{ in_array($key, $enabled) ? 'checked' : '' }}>
                        <label class="form-check-label" for="widget-{{ $key }}">{{ $label }}</label>
                    </div>
                    @endforeach
                </div>
                <div class="card-footer text-end">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
